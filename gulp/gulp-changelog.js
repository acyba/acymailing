const changelog = require('./changelog.js');
const gulp = require('gulp');
const fs = require('fs');
const path = require('path');
const types = {
    'feature': 'Features',
    'improve': 'Improvements',
    'fix': 'Bug fixes'
};

gulp.task('generate_changelog', function (done) {
    let versionsNumber = Object.keys(changelog)[0];
    getDateFormated();
    generateChangelogBeta(changelog[versionsNumber]);
    generateChangelogSVN(changelog[versionsNumber], versionsNumber);
    generateChangelogWebsite(changelog[versionsNumber], versionsNumber);
    done();
});

function generateChangelogBeta(changelog) {
    let final = '';
    Object.keys(types).map(type => {
        if (undefined === changelog[type]) return true;
        final += `${types[type]}\r\n`;
        changelog[type].map(log => {
            let cms = log.cms === 'all' ? '' : `[${log.cms}] `;
            final += `${cms}${log.message}\r\n`;
        });
        final += `\r\n`;
    });

    writeFileChangelog('tools/changelog/beta.txt', final);
}

function generateChangelogSVN(changelog, version) {
    let final = `= ${version} - ${getDateFormated()} =\r\n`;
    Object.keys(types).map(type => {
        if (undefined === changelog[type]) return true;
        changelog[type].map(log => {
            if (log.cms !== 'joomla') final += `* ${log.message}\r\n`;
        });
        final += `\r\n`;
    });

    writeFileChangelog('tools/changelog/svn.txt', final);
}

function generateChangelogWebsite(changelog, version) {

    let final = `<li class="version js-version active">
    <div class="version-title-date js-version-title">
        <h2 class="version-title">AcyMailing ${version}</h2>
        <div class="version-date">${getDateFormated()}</div>
        </div>
        <div class="version-content js-version-content">`;
    Object.keys(types).map(type => {
        if (undefined === changelog[type]) return true;
        final += `<h3>${types[type]}</h3>
        <ul>`;
        changelog[type].map(log => {
            let before = log.cms === 'all' ? `[jlogo/][wplogo/]` : (log.cms === 'joomla' ? `[jlogo/]` : `[wplogo/]`);
            final += `<li>${before}${log.message}</li>`;
        });
        final += `</ul>`;
    });
    final += `
    </div>
</li>`;

    writeFileChangelog('tools/changelog/website.txt', final);
}

function getDateFormated() {
    const dateobj = new Date();
    let month = dateobj.getMonth() + 1;
    let day = dateobj.getDate();
    let year = dateobj.getFullYear();

    const monthLetter = {
        1: 'January',
        2: 'February',
        3: 'March',
        4: 'April',
        5: 'May',
        6: 'June',
        7: 'July',
        8: 'August',
        9: 'September',
        10: 'October',
        11: 'November',
        12: 'December'
    };

    return `${monthLetter[month]} ${day}, ${year}`;
}

function writeFileChangelog(filename, fileContent) {
    let dirname = path.dirname(filename);
    if (!fs.existsSync(dirname)) {
        fs.mkdirSync(dirname);
    }
    fs.writeFileSync(filename, fileContent, 'utf-8');
}
