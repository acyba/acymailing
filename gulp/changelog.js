module.exports = {
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
