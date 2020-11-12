module.exports = {
    '6.19.0': {
        'feature': [
        ],
        'improve': [
        ],
        'fix': [
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
