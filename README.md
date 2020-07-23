# AcyMailing

This is the code of the AcyMailing Starter version.

## Setup

### Requirements

- NodeJs
- npm
- PHP 5.6

### Installation

We don't recommend to clone the repo inside the CMS.
```bash
#clone the repo
git clone https://

#go to the repo
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

Once this step is done open a new console at the root of AcyMailing's project:
```bash
#Start the synchronization
gulp default
```

This gulp task allows you to synchronize the AcyMailing folder on your WordPress and Joomla website.  
This means each time you make a modification on the source code then it will be applied on your test websites.

