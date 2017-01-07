module.exports = function (grunt) {
    // Project configuration.
    grunt.initConfig({
        modx: grunt.file.readJSON('_build/config.json'),
        sshconfig: grunt.file.readJSON('/Users/jako/Documents/MODx/partout.json'),
        banner: '/*!\n' +
        ' * <%= modx.name %> - <%= modx.description %>\n' +
        ' * Version: <%= modx.version %>\n' +
        ' * Build date: <%= grunt.template.today("yyyy-mm-dd") %>\n' +
        ' */\n',
        usebanner: {
            dist: {
                options: {
                    position: 'top',
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
                outputStyle: 'expanded',
                indentType: 'tab',
                indentWidth: 1,
                sourcemap: false
            },
            dist: {
                files: {
                    'source/css/fd.css': 'source/sass/fd.scss'
                }
            }
        },
        postcss: {
            options: {
                processors: [
                    require('pixrem')(),
                    require('autoprefixer')({
                        browsers: 'last 2 versions, ie >= 8'
                    })
                ]
            },
            dist: {
                src: [
                    'source/css/fd.css'
                ]

            }
        },
        cssmin: {
            filedownloadr: {
                src: [
                    'source/css/fd.css'
                ],
                dest: 'assets/components/filedownloadr/css/fd.min.css'
            }
        },
        sftp: {
            css: {
                files: {
                    "./": [
                        'assets/components/filedownloadr/css/web/fd.min.css'
                    ]
                },
                options: {
                    path: '<%= sshconfig.hostpath %>develop/filedownloadr/',
                    srcBasePath: 'develop/filedownloadr/',
                    host: '<%= sshconfig.host %>',
                    username: '<%= sshconfig.username %>',
                    privateKey: '<%= sshconfig.privateKey %>',
                    passphrase: '<%= sshconfig.passphrase %>',
                    showProgress: true
                }
            }
       },
        watch: {
            css: {
                files: [
                    'source/**/*.scss'
                ],
                tasks: ['sass', 'postcss', 'cssmin', 'usebanner:css', 'sftp:css']
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
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-banner');
    grunt.loadNpmTasks('grunt-ssh');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-postcss');
    grunt.loadNpmTasks('grunt-string-replace');
    grunt.renameTask('string-replace', 'bump');

    //register the task
    grunt.registerTask('default', ['bump', 'sass', 'postcss', 'cssmin', 'usebanner', 'sftp']);
};
