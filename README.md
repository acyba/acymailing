# AcyMailing

Welcome to the AcyMailing repository on GitHub. Here you can browse the source code, look at open issues and keep track of development.

This is the code of the AcyMailing Starter version.

If you are not a developer, please download AcyMailing [from our website](https://www.acymailing.com/#download) instead.

## Documentation
* [Documentation](https://docs.acymailing.com/)
* [Developer Documentation](https://docs.acymailing.com/developers/acymailing-developer-documentation)

## Support
This repository is not suitable for support. Please don't use our issue tracker for support requests, but for core AcyMailing issues only.  
Support requests in issues on this repository will be closed.

## Contributing to AcyMailing
If you have a patch or have stumbled upon an issue with AcyMailing, you can create a pull request on your fork, it will be reviewed by the AcyMailing development team.

## Setup

### Requirements

- npm
- PHP 5.6 minimum

### Installation

We don't recommend to clone the project inside the CMS to avoid losing your data when updating.
```bash
#fork the project

#clone your copy
git clone https://xxx

#go to the repository
cd acymailing

#install the dependencies
npm install
```

Once you've done that, you will have to create a new file `gulp-settings.js` in the folder gulp.
Here is the content of it:

```js
module.exports = {
    //you can set one or several joomla dev sites
    joomla: ['path/to/joomla', 'path/to/other/joomla'],
    //you can set one or several wordpress dev sites
    wordpress: ['path/to/wordpress'],
};
```

### Development

Before launching any further script please download and install the Starter version on your development site.  
You can download the latest version of AcyMailing [here](https://www.acymailing.com/account/license/)  

Once this step is done open a new console at the root of the AcyMailing project:
```bash
#Start the synchronization
gulp default
```

This gulp task will synchronize any modification made in the AcyMailing folder on your WordPress and Joomla websites.  
