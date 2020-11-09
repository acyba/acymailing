let settings = require('./gulp-settings.js');

let gulp = require('gulp');
let cache = require('gulp-cached');
let concat = require('gulp-concat');
let rename = require('gulp-rename');
let filter = require('gulp-filter');
let compass = require('gulp-compass');
let replace = require('gulp-replace');
let minifyJS = require('gulp-minify');
let minifyCSS = require('gulp-cssmin');
let multidest = require('gulp-multi-dest');
let command = require('gulp-run-command').default;
let babel = require('gulp-babel');

let version = '6.18.0';

let minifyJSOptions = {
    ext: {
        min: '.js'
    },
    noSource: true
};
let renameOptions = {
    suffix: '.min'
};
let compassOptions = {
    css: 'media/assets/css',
    sass: 'media/assets/scss'
};

let backFolder = [
    'back/**/*'
];

function getPaths(path, websites = settings.joomla) {
    let paths = [];
    for (let i = 0 ; i < websites.length ; i++) {
        paths[i] = websites[i] + path;
    }
    return paths;
}

gulp.task('synch_copy-files', gulp.series(function () {
    return gulp.src([
        'acym.xml',
        'install.joomla.php',
        'install.class.php',
        'LICENSE'
    ])
               .pipe(replace('{__LEVEL__}', 'enterprise'))
               .pipe(replace('{__CMS__}', 'Joomla'))
               .pipe(replace('{__VERSION__}', version))
               .pipe(multidest(getPaths('/administrator/components/com_acym')));
}, function () {
    return gulp.src([
        'index.php',
        'install.class.php',
        'LICENSE'
    ])
               .pipe(replace('{__LEVEL__}', 'enterprise'))
               .pipe(replace('{__CMS__}', 'WordPress'))
               .pipe(replace('{__VERSION__}', version))
               .pipe(multidest(getPaths('/wp-content/plugins/acymailing', settings.wordpress)));
}));

gulp.task('synch_copy-autoload', command('composer dumpautoload -o'));

gulp.task('synch_copy-vendor', gulp.series(function () {
    return gulp.src([
        'vendor/**',
        '!vendor/composer/autoload_classmap.php',
        '!vendor/composer/autoload_static.php'
    ]).pipe(multidest(getPaths('/administrator/components/com_acym/vendor')));
}, function () {
    return gulp.src([
        'vendor/composer/autoload_classmap.php',
        'vendor/composer/autoload_static.php'
    ])
               .pipe(replace('/back', ''))
               .pipe(replace('/front/', '/../../../components/com_acym/'))
               .pipe(multidest(getPaths('/administrator/components/com_acym/vendor/composer/')));
}, function () {
    return gulp.src([
        'vendor/**'
    ]).pipe(multidest(getPaths('/wp-content/plugins/acymailing/vendor', settings.wordpress)));
}));

gulp.task('synch_copy-back', gulp.series(function () {
    return gulp.src(backFolder)
               .pipe(cache('back'))
               .pipe(replace('{__CMS__}', 'Joomla'))
               .pipe(replace('{__VERSION__}', version))
               .pipe(multidest(getPaths('/administrator/components/com_acym')));
}, function () {
    return gulp.src(backFolder)
               .pipe(cache('wpback'))
               .pipe(replace('{__CMS__}', 'WordPress'))
               .pipe(replace('{__VERSION__}', version))
               .pipe(multidest(getPaths('/wp-content/plugins/acymailing/back', settings.wordpress)));
}));

gulp.task('synch_copy-front', gulp.series(function () {
    return gulp.src('front/**/*')
               .pipe(cache('front'))
               .pipe(replace('{__CMS__}', 'Joomla'))
               .pipe(replace('{__VERSION__}', version))
               .pipe(multidest(getPaths('/components/com_acym')));
}, function () {
    return gulp.src('front/**/*')
               .pipe(cache('wpfront'))
               .pipe(replace('{__CMS__}', 'WordPress'))
               .pipe(replace('{__VERSION__}', version))
               .pipe(multidest(getPaths('/wp-content/plugins/acymailing/front', settings.wordpress)));
}));

gulp.task('synch_copy-languages', gulp.series(function () {
    return gulp.src([
        'language/en-GB.com_acym.ini',
        'language/en-US.com_acym.ini',
        'language/fr-FR.com_acym.ini'
    ])
               .pipe(cache('language'))
               .pipe(rename((path) => {
                   path.dirname = '/' + path.basename.replace('.com_acym', '');
               }))
               .pipe(multidest(getPaths('/language')));
}, function () {
    return gulp.src('language/*.ini')
               .pipe(cache('wplanguage'))
               .pipe(multidest(getPaths('/wp-content/uploads/acymailing/language', settings.wordpress)));
}));

let babelled = [
    [
        'global/helpers/editor_wysid/*.js',
        'editor_wysid_utils.js'
    ],
    [
        'back/*.js',
        'back_global.js'
    ],
    [
        'front/*.js',
        'front_global.js'
    ],
    [
        'back/helpers/*.js',
        'back_helpers.js'
    ],
    [
        'global/helpers/*.js',
        'helpers.js'
    ],
    [
        'front/helpers/*.js',
        'front_helpers.js'
    ]
];

let simple = [
    [
        'back/controllers/*.js',
        '/back'
    ],
    [
        'back/controllers/**/*.js',
        '/back'
    ],
    [
        'back/vue/applications/*.js',
        '/vue'
    ],
    [
        'front/controllers/*.js',
        '/front'
    ],
    [
        'front/controllers/**/*.js',
        '/front'
    ],
    [
        'libraries/*.js',
        '/libraries'
    ],
    [
        '*.js',
        ''
    ]
];

let groupped = [
    [
        'back/vue/components/*.js',
        'vue_components.js',
        '/vue'
    ]
];

gulp.task('clear_cache', (done) => {
    cache.caches = {};
    done();
});

gulp.task('synch_handle-js', gulp.parallel(function (done) {
    babelled.forEach(function (filePath) {
        gulp.src('media/assets/js/' + filePath[0])
            .pipe(concat(filePath[1]))
            .pipe(babel({presets: ['@babel/env']}))
            .pipe(minifyJS(minifyJSOptions))
            .pipe(rename(renameOptions))
            .pipe(gulp.dest('media/js'));
    });

    done();
}, function (done) {
    simple.forEach(function (filePath) {
        gulp.src('media/assets/js/' + filePath[0])
            .pipe(minifyJS(minifyJSOptions))
            .pipe(rename(renameOptions))
            .pipe(gulp.dest('media/js' + filePath[1]));
    });

    done();
}, function (done) {
    groupped.forEach(function (filePath) {
        gulp.src('media/assets/js/' + filePath[0])
            .pipe(concat(filePath[1]))
            .pipe(minifyJS(minifyJSOptions))
            .pipe(rename(renameOptions))
            .pipe(gulp.dest('media/js' + filePath[2]));
    });

    done();
}));

let scssBlocks = [
    'back/*.scss',
    'front/*.scss',
    'libraries/*.scss',
    '*.scss'
];

gulp.task('synch_handle-css', gulp.parallel(function (done) {
    scssBlocks.forEach(function (files) {
        gulp.src('media/assets/scss/' + files)
            .pipe(compass(compassOptions))
            .pipe(minifyCSS())
            .pipe(rename(renameOptions))
            .pipe(gulp.dest('media/css'));
    });

    done();
}, function () {
    return gulp.src('media/assets/scss/fonts/*')
               .pipe(gulp.dest('media/css/fonts'));
}));

gulp.task('synch_copy-media', gulp.series(function () {
    return gulp.src([
        'media/**/*',
        '!media/assets/**',
        '!media/assets',
        '!media/images/plugins/**',
        '!media/images/plugins'
    ])
               .pipe(cache('media'))
               .pipe(multidest(getPaths('/media/com_acym')));
}, function () {
    return gulp.src([
        'media/**/*',
        '!media/assets/**',
        '!media/assets'
    ])
               .pipe(cache('wpmedia'))
               .pipe(multidest(getPaths('/wp-content/plugins/acymailing/media', settings.wordpress)));
}));

gulp.task('synch_copy-modules', gulp.series(function () {
    return gulp.src('modules/**/*')
               .pipe(multidest(getPaths('/modules')));
}, function () {
    return gulp.src('widgets/**/*')
               .pipe(multidest(getPaths('/wp-content/plugins/acymailing/widgets', settings.wordpress)));
}));

gulp.task('synch_copy-plugins', gulp.series(function () {
    return gulp.src('plugins/joomla/plg_*/*')
               .pipe(cache('plugins'))
               .pipe(rename((path) => {
                   path.dirname = path.dirname.replace(/^plg_([a-zA-Z0-9]+)_([a-zA-Z0-9]+)\/?/, '$1/$2/');
               }))
               .pipe(multidest(getPaths('/plugins')));
}, function () {
    return gulp.src('plugins/joomla/plug_*/*')
               .pipe(cache('pluginscb'))
               .pipe(multidest(getPaths('/components/com_comprofiler/plugin/user')));
}, function () {
    return gulp.src('plugins/wordpress/**/*')
               .pipe(cache('pluginswp'))
               .pipe(multidest(getPaths('/wp-content/plugins', settings.wordpress)));
}));

gulp.task('synch_copy-addons', gulp.series(function () {
    return gulp.src('addons/**/*')
               .pipe(cache('addons'))
               .pipe(multidest(getPaths('/administrator/components/com_acym/dynamics')));
}, function () {
    let mask = [
        'addons/page/**',
        'addons/post/**'
    ];
    return gulp.src('addons/**/*')
               .pipe(cache('addonswpcore'))
               .pipe(filter(mask))
               .pipe(multidest(getPaths('/wp-content/plugins/acymailing/back/dynamics', settings.wordpress)));
}, function () {
    let mask = [
        'addons/**',
        '!addons/page/**',
        '!addons/page',
        '!addons/post/**',
        '!addons/post'
    ];
    return gulp.src('addons/**/*')
               .pipe(cache('addonswp'))
               .pipe(filter(mask))
               .pipe(multidest(getPaths('/wp-content/uploads/acymailing/addons', settings.wordpress)));
}));

gulp.task('synch_copy-wpinit', function () {
    return gulp.src('wpinit/**/*')
               .pipe(cache('wpinit'))
               .pipe(replace('{__CMS__}', 'WordPress'))
               .pipe(replace('{__VERSION__}', version))
               .pipe(multidest(getPaths('/wp-content/plugins/acymailing/wpinit', settings.wordpress)));
});

gulp.task('synch_copy-all', gulp.parallel(
    'synch_copy-files',
    gulp.series('synch_copy-autoload', 'synch_copy-vendor'),
    'synch_copy-back',
    'synch_copy-front',
    gulp.series(gulp.parallel('synch_handle-js', 'synch_handle-css'), 'synch_copy-media'),
    'synch_copy-languages',
    'synch_copy-modules',
    'synch_copy-plugins',
    'synch_copy-addons',
    'synch_copy-wpinit'
));

gulp.task('synch_watch', function () {
    gulp.watch('*', gulp.series('synch_copy-files'));
    gulp.watch('*', gulp.series('synch_copy-vendor'));
    gulp.watch('addons/**/*', gulp.series('synch_copy-addons'));
    gulp.watch(backFolder, gulp.series('synch_copy-back'));
    gulp.watch('front/**/*', gulp.series('synch_copy-front'));
    gulp.watch('language/*', gulp.series('synch_copy-languages'));
    gulp.watch([
        'media/**/*',
        '!media/assets/**',
        '!media/assets'
    ], gulp.series('synch_copy-media'));
    gulp.watch('media/assets/js/**/*', gulp.series('synch_handle-js'));
    gulp.watch('media/assets/scss/**/*', gulp.series('synch_handle-css'));
    gulp.watch('modules/**/*', gulp.series('synch_copy-modules'));
    gulp.watch('plugins/**/*', gulp.series('synch_copy-plugins'));
    gulp.watch('widgets/**/*', gulp.series('synch_copy-modules'));
    gulp.watch('wpinit/**/*', gulp.series('synch_copy-wpinit'));
});

gulp.task('Prepare release', gulp.series(function (done) {
    minifyJSOptions.ignoreFiles = [];
    done();
}, 'synch_copy-all', 'synch_watch'));

gulp.task('minify_release', gulp.series(function (done) {
    minifyJSOptions.ignoreFiles = [];
    done();
}, 'clear_cache', 'synch_copy-all'));

gulp.task('default', gulp.series(function (done) {
    minifyJSOptions.ignoreFiles = ['*.js'];
    backFolder.push('!back/partial/update/new_features.php');
    done();
}, 'clear_cache', 'synch_copy-all', 'synch_watch'));
