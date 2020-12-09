module.exports = {
    '7.0.0': {
        'improve': [
            {
                cms: 'all',
                message: 'A new access level entry has been added for the segments'
            },
            {
                cms: 'all',
                message: 'Add a start date for the automatic campaigns'
            },
            {
                cms: 'all',
                message: 'Add list description in list selection popup'
            }
        ]
    },
    '6.19.1': {
        'fix': [
            {
                cms: 'all',
                message: 'Fixed v5 data migration for the custom fields'
            },
            {
                cms: 'all',
                message: 'Fixed date display with timezone when not needed'
            },
            {
                cms: 'all',
                message: 'Fixed mail not found on the walk through for mails with non latin letter'
            },
            {
                cms: 'all',
                message: 'Fixed front archive breaking pages on some specific html structure'
            },
            {
                cms: 'all',
                message: 'Fixed dashboard every time when user do not have the rights to delete files'
            },
            {
                cms: 'all',
                message: 'Fixed migration'
            }

        ]
    },
    '6.19.0': {
        'feature': [
            {
                cms: 'all',
                message: 'You now have the information on which device your users are opening your newsletters'
            },
            {
                cms: 'all',
                message: 'New chart of the open time over the week'
            },
            {
                cms: 'all',
                message: 'New tab in the statistics menu: Links details'
            },
            {
                cms: 'all',
                message: 'New tab in the statistics menu: User clicks details'
            },
            {
                cms: 'all',
                message: 'A new add-on lets you automatically generate a table of contents for your emails'
            },
            {
                cms: 'wordpress',
                message: 'New integration with MemberPress'
            }
        ],
        'improve': [
            {
                cms: 'all',
                message: 'The email sending speed has been increased'
            },
            {
                cms: 'all',
                message: 'Add a counter for new subscribers/unsubscribers per list during the last month'
            },
            {
                cms: 'all',
                message: 'New option to group articles by category when inserted in an email'
            },
            {
                cms: 'all',
                message: 'The dynamic texts are now handled in the links inserted in your emails'
            },
            {
                cms: 'all',
                message: 'You can now apply actions on several templates at the same time (delete and duplicate)'
            },
            {
                cms: 'all',
                message: 'The loading of the statistics is faster'
            },
            {
                cms: 'all',
                message: 'You can now add images from an external source'
            },
            {
                cms: 'all',
                message: 'Add an evolution of subscribers graph in list edition'
            },
            {
                cms: 'all',
                message: 'New option to add attachments to welcome email, unsubscribe email, followup, automation emails'
            }
        ],
        'fix': [
            {
                cms: 'all',
                message: 'The bounce handling frequency can now be modified in the configuration'
            },
            {
                cms: 'all',
                message: 'Fixed automation triggered not the right users when multiple automation to run'
            },
            {
                cms: 'all',
                message: 'The dynamic date insertion in emails now takes the site\'s timezone into account'
            },
            {
                cms: 'all',
                message: 'Fixed button border radius on outlook desktop'
            },
            {
                cms: 'all',
                message: 'Fixed filter list by tag'
            },
            {
                cms: 'wordpress',
                message: 'Fixed automation with trigger once a day triggered at every cron'
            },
            {
                cms: 'wordpress',
                message: 'Fixed PHP warning on shortcode form edition'
            },
            {
                cms: 'wordpress',
                message: 'Compatibility with imagify and MailChimp for WooCommerce plugins on editor page'
            },
            {
                cms: 'joomla',
                message: 'The email override system doesn\'t prevent Akeeba Ticket System\'s emails from being sent anymore'
            },
            {
                cms: 'joomla',
                message: 'Fixed list access in welcome or unsubscribe email creation'
            }
        ]
    },
    '6.18.1': {
        'feature': [],
        'improve': [],
        'fix': [
            {
                cms: 'all',
                message: 'Fixed dynamic text {list:names} displaying invisible lists'
            },
            {
                cms: 'all',
                message: 'Fixed custom field not displaying if they had custom in the name'
            },
            {
                cms: 'all',
                message: 'The email names/subjects are now correctly displayed in the "Remove an email from the queue" action in the automation'
            },
            {
                cms: 'all',
                message: 'Fixed mail save not working on some emails'
            },
            {
                cms: 'all',
                message: 'The image selection now works correctly in the popup form builder'
            },
            {
                cms: 'joomla',
                message: 'Fix background image display in the front campaign edition'
            }
        ]
    },
    '6.18.0': {
        'feature': [
            {
                cms: 'all',
                message: 'A new follow-up feature has been added to let you plan automated emails on user actions'
            },
            {
                cms: 'all',
                message: 'You can now override the core emails of your site and send them with AcyMailing'
            }
        ],
        'improve': [
            {
                cms: 'all',
                message: 'You can now mention the article author when inserting it in an email'
            },
            {
                cms: 'joomla',
                message: 'The front-end template\'s styles are not loaded on the online version of an email anymore'
            },
            {
                cms: 'joomla',
                message: 'Added handling of custom media folder for image insertion in the editor'
            },
            {
                cms: 'all',
                message: 'Added handling of foreign key in the database check script'
            }
        ],
        'fix': [
            {
                cms: 'all',
                message: 'Fixed blank regex option in custom field after saving'
            },
            {
                cms: 'all',
                message: 'Fixed email search for special characters in segment select search'
            },
            {
                cms: 'wordpress',
                message: 'Fixed confirm redirection blank page on some WordPress websites'
            },
            {
                cms: 'all',
                message: 'Display search field on the detailed stats when no data found'
            },
            {
                cms: 'all',
                message: 'The image alignment now works correctly on old versions of Outlook'
            },
            {
                cms: 'all',
                message: 'The migration process from AcyMailing 5 might not import welcome or unsubscribe email'
            },
            {
                cms: 'all',
                message: 'The migration process from AcyMailing 5 might crash on newsletters migration'
            },
            {
                cms: 'all',
                message: 'Fixed stats export for hebrew'
            },
            {
                cms: 'all',
                message: 'Fixed licenses linking button for website with a lot of user groups'
            },
            {
                cms: 'all',
                message: 'Fixed SQL error on the cron when multilingual option is deactivated'
            }
        ]
    },
    '6.17.1': {
        'fix': [
            {
                cms: 'joomla',
                message: 'Fixed date for scheduled campaign showing with the timezone'
            }
        ]
    },
    '6.17.0': {
        'feature': [
            {
                cms: 'all',
                message: 'Added segment feature to filter the recipients on the campaign workflow'
            },
            {
                cms: 'wordpress',
                message: 'Added automatic mails for unfinished orders in WooCommerce'
            },
            {
                cms: 'joomla',
                message: '[Addon] New integration for CB Subscriptions, to filter users in the automations'
            },
            {
                cms: 'wordpress',
                message: 'The pages can now be overridden by copying the AcyMailing views (see developer documentation)'
            },
            {
                cms: 'all',
                message: 'Added birthday campaign'
            }
        ],
        'improve': [
            {
                cms: 'all',
                message: 'Improved performances and loading speed for the statistics page'
            },
            {
                cms: 'all',
                message: 'The detailed statistics now show the user name'
            },
            {
                cms: 'all',
                message: 'Better display for all the listings (buttons, selects, text inputs...)'
            },
            {
                cms: 'all',
                message: 'You can now choose the delay for a subscription form to be displayed again'
            }
        ],
        'fix': [
            {
                cms: 'all',
                message: 'Fixed php error in user edition if an email had an empty send date'
            },
            {
                cms: 'all',
                message: 'The HTML campaigns can now be saved correctly'
            },
            {
                cms: 'all',
                message: 'Fixed button generation for outlook in Firefox'
            },
            {
                cms: 'all',
                message: 'The emails are not duplicated anymore each time they are modified in the automations'
            },
            {
                cms: 'all',
                message: 'The space block of our editor now works correctly in Outlook'
            },
            {
                cms: 'all',
                message: 'AcyMailing now correctly recalls when a subscription form has been submitted/discarded and doesn\'t show it again on the other pages'
            },
            {
                cms: 'joomla',
                message: 'Compatibility issue with the sef URLs in some cases for the archive'
            }
        ]
    },
    '6.16.0': {
        'feature': [
            {
                cms: 'all',
                message: 'Added options to select Terms and Conditions and Privacy Policy in form builder'
            },
            {
                cms: 'wordpress',
                message: 'New plugins created to replace the add-ons'
            },
            {
                cms: 'wordpress',
                message: 'New WooCommerce trigger: when an order status change to another'
            },
            {
                cms: 'joomla',
                message: 'New add-on Community Surveys '
            }
        ],
        'improve': [
            {
                cms: 'all',
                message: 'Add several display options for the success message after subscription (only for the widget and module)'
            },
            {
                cms: 'all',
                message: 'You can now order and sort in the user listing in list edition'
            },
            {
                cms: 'all',
                message: 'You can now add list description to your newsletter with the dynamic text'
            },
            {
                cms: 'wordpress',
                message: 'We don\'t load AcyMailing translation files anymore'
            }
        ],
        'fix': [
            {
                cms: 'all',
                message: 'Remove links which users don\'t have access'
            },
            {
                cms: 'all',
                message: 'Display user values when clicking on a "modify my profile" link'
            },
            {
                cms: 'all',
                message: 'Fixed DKIM failing with custom generated keys'
            },
            {
                cms: 'all',
                message: 'Fixed queue execution for users with a lot of data'
            },
            {
                cms: 'all',
                message: 'Fixed generated campaign send and cancel buttons'
            },
            {
                cms: 'joomla',
                message: 'Fixed button on HTML editor'
            },
            {
                cms: 'all',
                message: 'Fixed CSS and pagination issues on archive menu and widget'
            },
            {
                cms: 'all',
                message: 'Fixed welcome and unsubscribe email creation, the list selected wasn\'t saved'
            },
            {
                cms: 'all',
                message: 'Fixed multilingual notification showing during the walkthrough'
            },
            {
                cms: 'all',
                message: 'Fixed email subject in the email history'
            },
            {
                cms: 'all',
                message: 'Fixed users entity select for users without name'
            }
        ]
    }
};
