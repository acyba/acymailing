<?php

namespace AcyMailing\FrontControllers\Api;

use AcyMailing\Classes\MailClass;

trait Templates
{
    public function getOneTemplate(): void
    {
        $templateId = acym_getVar('int', 'templateId', '');

        if (empty($templateId)) {
            $this->sendJsonResponse(['message' => 'Template ID not provided in the request.'], 422);
        }

        $mailClass = new MailClass();
        $template = $mailClass->getOneById($templateId);

        if (empty($template) || $template->type !== MailClass::TYPE_TEMPLATE) {
            $this->sendJsonResponse(['message' => 'Template not found.'], 404);
        }

        $cleanTmpl = $this->removeExtraColumns(self::TYPE_TEMPLATE, $template);

        $this->sendJsonResponse([$cleanTmpl]);
    }

    public function getTemplates(): void
    {
        $mailClass = new MailClass();

        try {
            $templates = $mailClass->getAllTemplatesByType(
                [
                    'offset' => acym_getVar('int', 'offset', 0),
                    'limit' => acym_getVar('int', 'limit', 20),
                    'filters' => acym_getVar('array', 'filters', ''),
                ]
            );

            foreach ($templates as $i => $oneTemplate) {
                $templates[$i] = $this->removeExtraColumns(self::TYPE_TEMPLATE, $oneTemplate);
            }
            $this->sendJsonResponse($templates);
        } catch (\Exception $e) {
            $this->sendJsonResponse(['message' => 'Error getting the collection of templates: '.$e->getMessage()], 500);
        }
    }

    public function createTemplate(): void
    {
        $decodedData = acym_getJsonData();

        if (empty($decodedData['name'])) {
            $this->sendJsonResponse(['message' => 'Missing template name.'], 422);
        }
        $template = new \stdClass();
        $template->drag_editor = 0;
        $template->type = MailClass::TYPE_TEMPLATE;
        foreach (self::AVAILABLE_TMPL_COLUMNS as $oneColumn) {
            $template->$oneColumn = $decodedData[$oneColumn] ?? '';
        }

        $mailCLass = new MailClass();
        $tmplId = $mailCLass->save($template);

        if (empty($tmplId)) {
            $this->sendJsonResponse(['message' => 'Error creating template.', 'errors', $mailCLass->errors], 500);
        } else {
            $this->sendJsonResponse(['templateId' => $tmplId], 201);
        }
    }

    public function updateTemplate(): void
    {
        $decodedData = acym_getJsonData();

        if (empty($decodedData['id']) || !is_int($decodedData['id'])) {
            $this->sendJsonResponse(['message' => 'Missing or incorrect template ID.'], 422);
        }

        $mailClass = new MailClass();
        $template = $mailClass->getOneById($decodedData['id']);
        if (empty($template)) {
            $this->sendJsonResponse(['message' => 'Template not found.'], 404);
        }

        if ($template->type != MailClass::TYPE_TEMPLATE || $template->drag_editor != 0) {
            $this->sendJsonResponse(['message' => 'You can\'t edit this template.'], 403);
        }

        foreach (self::AVAILABLE_TMPL_COLUMNS as $oneColumn) {
            if (!empty($decodedData[$oneColumn])) {
                $template->$oneColumn = $decodedData[$oneColumn];
            }
        }

        $mailCLass = new MailClass();
        $tmplId = $mailCLass->save($template);
        if (empty($tmplId)) {
            $this->sendJsonResponse(['message' => 'Error modifying template.', 'errors' => $mailCLass->errors], 500);
        } else {
            $this->sendJsonResponse(['templateId' => $tmplId]);
        }
    }

    public function deleteTemplate(): void
    {
        $templateId = acym_getVar('int', 'templateId', '');

        if (empty($templateId)) {
            $this->sendJsonResponse(['message' => 'Template ID not provided in the request.'], 422);
        }

        $mailClass = new MailClass();
        $template = $mailClass->getOneById($templateId);

        if (empty($template) || $template->type !== MailClass::TYPE_TEMPLATE) {
            $this->sendJsonResponse(['message' => 'Template not found.'], 404);
        }

        if ($mailClass->delete([$templateId])) {
            $this->sendJsonResponse(['message' => 'Template deleted.']);
        } else {
            $this->sendJsonResponse(['message' => 'Failed deleting template.', 'errors' => $mailClass->errors], 500);
        }
    }
}
