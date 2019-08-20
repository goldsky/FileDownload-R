module.exports = function (grunt) {
    // Project configuration.
    grunt.initConfig({
        modx: grunt.file.readJSON('_build/config.json'),
        banner: '/*!\n' +
        ' * <%= modx.name %> - <%= modx.description %>\n' +
        ' * Version: <%= modx.version %>\n' +
        ' * Build date: <%= grunt.template.today("yyyy-mm-dd") %>\n' +
        ' */\n',
        usebanner: {
            css: {
                options: {
                    position: 'bottom',
                    banner: '<%= banner %>'
                },
                files: {
                    src: [
                        'assets/components/filedownloadr/css/fd.min.css'
                    ]
                }
            }
        },
        sass: {
            options: {
                implementation: require('node-sass'),
                outputStyle: 'expanded',
                sourcemap: false
            },
            web: {
                files: {
                    'source/css/fd.css': 'source/sass/fd.scss'
                }
            }
        },
        postcss: {
            options: {
                processors: [
                    require('pixrem')(),
                    require('autoprefixer')()
                ]
            },
            web: {
                src: [
                    'source/css/fd.css'
                ]
            }
        },
        cssmin: {
            web: {
                src: [
                    'source/css/fd.css'
                ],
                dest: 'assets/components/filedownloadr/css/fd.min.css'
            }
        },
        watch: {
            css: {
                files: [
                    'source/**/*.scss'
                ],
                tasks: ['sass', 'postcss', 'cssmin', 'usebanner:css']
            },
            config: {
                files: [
                    '_build/config.json'
                ],
                tasks: ['default']
            }
        },
        bump: {
            copyright: {
                files: [{
                    src: 'core/components/filedownloadr/model/filedownloadr/filedownloadr.class.php',
                    dest: 'core/components/filedownloadr/model/filedownloadr/filedownloadr.class.php'
                }],
                options: {
                    replacements: [{
                        pattern: /Copyright 2011(-\d{4})? by/g,
                        replacement: 'Copyright ' + (new Date().getFullYear() > 2011 ? '2011-' : '') + new Date().getFullYear() + ' by'
                    }]
                }
            },
            version: {
                files: [{
                    src: 'core/components/filedownloadr/model/filedownloadr/filedownloadr.class.php',
                    dest: 'core/components/filedownloadr/model/filedownloadr/filedownloadr.class.php'
                }],
                options: {
                    replacements: [{
                        pattern: /version = '\d+.\d+.\d+[-a-z0-9]*'/ig,
                        replacement: 'version = \'' + '<%= modx.version %>' + '\''
                    }]
                }
            }
        }
    });

    //load the packages
    grunt.loadNpmTasks('grunt-banner');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-postcss');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-string-replace');
    grunt.renameTask('string-replace', 'bump');

    //register the task
    grunt.registerTask('default', ['bump', 'sass', 'postcss', 'cssmin', 'usebanner']);
};
