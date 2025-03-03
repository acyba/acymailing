<?php

namespace AcyMailing\Controllers\Campaigns;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\UpdatemeHelper;


trait Tests
{
    public function checkContent(): void
    {
        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();

        $campaignId = acym_getVar('int', 'campaignId', 0);
        $campaign = $campaignClass->getOneById($campaignId);

        if (empty($campaign)) {
            acym_sendAjaxResponse(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), [], false);
        }

        $mailId = acym_getVar('int', 'mailId', 0);
        $mail = $mailClass->getOneById(empty($mailId) ? $campaign->mail_id : $mailId);

        $result = '';
        $spamWords = [
            '4U',
            'you are a winner',
            'For instant access',
            'Accept credit cards',
            'Claims you registered with',
            'For just $',
            'Act now!',
            'Don’t hesitate!',
            'Click below',
            'Free',
            'income',
            'Click here',
            'Click to remove',
            'All natural',
            'Amazing',
            'Compare rates',
            'Apply Online',
            'your business',
            'As seen on',
            'all orders',
            'Auto email removal',
            'bankruptcy',
            'debt',
            'Be amazed',
            'Copy accurately',
            'Be your own boss',
            'Being a member',
            'Big bucks',
            'Credit card',
            'Bill',
            'Cures baldness',
            'Billing address',
            'Billion dollars',
            'Dear friend',
            'Brand new pager',
            'Bulk email',
            'Different reply to',
            'Buy direct',
            'Dig up dirt',
            'Full refund',
            'Buying judgments',
            'Direct email',
            'Get It Now',
            'Cable converter',
            'Direct marketing',
            'Get paid',
            'Get started now',
            'Call now',
            'Do it today',
            'Gift certificate',
            'Calling creditors',
            'Don’t delete',
            'Great offer',
            'Can’t live without',
            'Drastically reduced',
            'Guarantee',
            'Cancel at any time',
            'Earn per week',
            'Have you been turned down?',
            'Easy terms',
            'Hidden assets',
            'Eliminate bad credit',
            'Home employment',
            'Cash',
            'Email harvest',
            'Human growth hormone',
            'Casino',
            'Email marketing',
            'Expect to earn',
            'In accordance with laws',
            'Fantastic deal',
            'Increase sales',
            'Viagra',
            'Increase traffic',
            'Insurance',
            'Find out anything',
            'Investment decision',
            'it\'s legal',
            'It\'s effective',
            'Join millions of',
            'No questions asked',
            'Reverses aging',
            'No selling',
            'Risk',
            'Limited time only',
            'No strings attached',
            'Round the world',
            'Not intended',
            'Lose weight',
            'Off shore',
            'Safeguard notice',
            'Lower interest rates',
            'Offer expires',
            'Satisfaction guaranteed',
            'Lower monthly payment',
            'coupon',
            'Save $',
            'Lowest price',
            'Luxury car',
            'Save up to',
            'Once in a lifetime',
            'Score with babes',
            'Marketing solutions',
            'Mass email',
            'guaranteed',
            'See for yourself',
            'Meet singles',
            'One time mailing',
            'Sent in compliance',
            'Member stuff',
            'opportunity',
            'Online pharmacy',
            'Serious only',
            'MLM',
            'Only $',
            'Shopping spree',
            'Social security number',
            'trial offer',
            'Special promotion',
            'More Internet traffic',
            'Stock alert',
            'Outstanding values',
            'Pennies a day',
            'Stock pick',
            'New customers only',
            'money',
            'Stop snoring',
            'New domain extensions',
            'Please read',
            'Strong buy',
            'Potential earnings',
            'Stuff on sale',
            'No age restrictions',
            'Subject to credit',
            'No catch',
            'Supplies are limited',
            'No claim forms',
            'Produced and sent out',
            'Take action now',
            'No cost',
            'Profits',
            'hidden charges',
            'No credit check',
            'Promise you',
            'No disappointment',
            'Pure profit',
            'Real thing',
            'No fees',
            'Refinance home',
            'The best rates',
            'No gimmick',
            'The following form',
            'No inventory',
            'No investment',
            'giving it away',
            'No medical exams',
            'Removes wrinkles',
            'This isn’t junk',
            'No middleman',
            'This isn’t spam',
            'No obligation',
            'initial investment',
            'University diplomas',
            'No purchase necessary',
            'Reserves the right',
            'Unlimited',
            'We honor all',
            'Will not believe your eyes',
            'Urgent',
            'Winner',
            'US dollars',
            'What are you waiting for?',
            'Winning',
            'While supplies last',
            'Work at home',
            'drugs',
            'While you sleep',
            'You have been selected',
            'We hate spam',
            'Why pay more?',
        ];

        $spamWordsInContent = [];
        foreach ($spamWords as $oneWord) {
            if ((bool)preg_match('#'.preg_quote($oneWord, '#').'#Uis', $mail->subject.$mail->body)) {
                $spamWordsInContent[] = $oneWord;
            }
        }

        if (count($spamWordsInContent) > 2) {
            $result = acym_translation('ACYM_TESTS_CONTENT_DESC');
            $result .= '<ul class="acym__ul"><li>'.implode('</li><li>', $spamWordsInContent).'</li></ul>';
        }

        acym_sendAjaxResponse('', ['result' => $result]);
    }

    public function checkLinks(): void
    {
        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();

        $campaignId = acym_getVar('int', 'campaignId', 0);
        $campaign = $campaignClass->getOneById($campaignId);

        if (empty($campaign)) {
            acym_sendAjaxResponse(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), [], false);
        }

        $mailId = acym_getVar('int', 'mailId', 0);
        $mail = $mailClass->getOneById(empty($mailId) ? $campaign->mail_id : $mailId);

        acym_trigger('replaceContent', [&$mail, false]);
        $userClass = new UserClass();
        $receiver = $userClass->getOneByEmail(acym_currentUserEmail());
        if (empty($receiver)) {
            $receiver = new \stdClass();
            $receiver->email = acym_currentUserEmail();
            $newID = $userClass->save($receiver);
            $receiver = $userClass->getOneById($newID);
        }
        acym_trigger('replaceUserInformation', [&$mail, &$receiver, false]);

        preg_match_all('# (href|src)="([^"]+)"#Uis', acym_absoluteURL($mail->body), $URLs);

        $brokenLinks = [];
        $processed = [];
        $result = '';
        foreach ($URLs[2] as $oneURL) {
            if (in_array($oneURL, $processed)) continue;
            if (0 === strpos($oneURL, 'mailto:')) continue;
            if (0 === strpos($oneURL, 'tel:')) continue;
            if (strlen($oneURL) > 1 && (0 === strpos($oneURL, '#') || false !== strpos($oneURL, 'unsubscribe'))) continue;

            $processed[] = $oneURL;

            $headers = @get_headers($oneURL);
            $headers = is_array($headers) ? implode("\n ", $headers) : $headers;

            if (empty($headers) || preg_match('#^HTTP/.*\s+[(200|301|302|304)]+\s#i', $headers) !== 1) {
                $brokenLinks[] = '<a target="_blank" href="'.$oneURL.'">'.(strlen($oneURL) > 50 ? substr($oneURL, 0, 25).'...'.substr($oneURL, strlen($oneURL) - 20)
                        : $oneURL).'</a>';
            }
        }

        if (!empty($brokenLinks)) {
            $result = '<ul class="acym__ul"><li>'.implode('</li><li>', $brokenLinks).'</li></ul>';
        }

        acym_sendAjaxResponse('', ['result' => $result]);
    }

    public function checkSPAM(): void
    {
        $message = '';
        $data = [];
        $success = false;

        $campaignId = acym_getVar('int', 'campaignId', 0);
        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneByIdWithMail($campaignId);

        $mailId = acym_getVar('int', 'mailId', 0);
        if (empty($mailId)) {
            $mailId = $campaign->mail_id;
        }

        if (empty($mailId)) {
            $message = acym_translation('ACYM_CAMPAIGN_NOT_FOUND');
        } else {
            ob_start();
            $urlSite = trim(base64_encode(preg_replace('#https?://(www2?\.)?#i', '', ACYM_LIVE)), '=/');
            $level = strtolower($this->config->get('level', 'starter'));
            $spamtestSystem = UpdatemeHelper::call('public/getSpamSystem?level='.$level.'&urlSite='.$urlSite);
            $warnings = ob_get_clean();

            // Could not load the information
            if (empty($spamtestSystem) || !empty($warnings)) {
                $message = acym_translation('ACYM_ERROR_LOAD_FROM_ACYBA').(!empty($warnings) && acym_isDebug() ? $warnings : '');
            } else {
                if (!empty($spamtestSystem['messages']) || !empty($spamtestSystem['error'])) {
                    $msgError = empty($spamtestSystem['messages']) ? '' : $spamtestSystem['messages'].'<br />';
                    $msgError .= empty($spamtestSystem['error']) ? '' : $spamtestSystem['error'];
                    $message = $msgError;
                } else {
                    if (empty($spamtestSystem['email'])) {
                        $message = acym_translation('ACYM_SPAMTEST_MISSING_EMAIL');
                    } else {
                        $mailerHelper = new MailerHelper();
                        $mailerHelper->report = false;

                        //send a message to acy-WEBSITE-randnumber@mail-tester.com
                        $receiver = new \stdClass();
                        $receiver->id = 0;
                        $receiver->email = $spamtestSystem['email'];
                        $receiver->name = $spamtestSystem['name'];
                        $receiver->confirmed = 1;
                        $receiver->enabled = 1;
                        $mailerHelper->isSpamTest = true;

                        if ($mailerHelper->sendOne($mailId, $receiver)) {
                            $success = true;
                            $data['url'] = 'https://mailtester.acyba.com/'.(substr($spamtestSystem['email'], 0, strpos($spamtestSystem['email'], '@')));
                            $data['lang'] = acym_getLanguageTag(true);
                        } else {
                            $message = $mailerHelper->reportMessage;
                        }
                    }
                }
            }
        }

        acym_sendAjaxResponse($message, $data, $success);
    }
}
