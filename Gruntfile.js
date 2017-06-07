module.exports = function (grunt) {
    // Project configuration.

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        gitclone: {
            clone: {
                options: {
                    repository: 'https://github.com/Desertsnowman/context',
                    branch: 'master',
                    directory: 'context-install'
                }
            }
        },
        copy: {
            main: {
                files: [
                    {
                        expand: false,
                        cwd: './',
                        src: 'context-install/context-plugin-bootstrap.php',
                        dest: "<%= pkg.plugin_name.toLocaleLowerCase().replace(/[^a-z0-9]/gi, '-') %>-bootstrap.php"
                    },
                    {
                        expand: false,
                        cwd: './',
                        src: 'context-install/context-plugin.php',
                        dest: "<%= pkg.plugin_name.toLocaleLowerCase().replace(/[^a-z0-9]/gi, '-' ) %>.php"
                    },
                    {
                        expand: false,
                        cwd: './',
                        src: 'context-install/composer.json',
                        dest: "composer.json"
                    },
                    {
                        expand: false,
                        cwd: './',
                        src: 'context-install/apigen.neon',
                        dest: "apigen.neon"
                    },
                    {
                        expand: true,
                        cwd: './context-install/includes/',
                        src: '**',
                        dest: 'includes/'
                    },
                    {
                        expand: false,
                        cwd: './',
                        src: 'context-install/classes/class-context-plugin.php',
                        dest: "classes/class-<%= pkg.plugin_name.toLocaleLowerCase().replace(/[^a-z0-9]/gi, '-' ) %>.php"
                    },
                    {
                        expand: true,
                        cwd: './context-install/languages/',
                        src: '**',
                        dest: 'languages/'
                    },
                    {
                        expand: false,
                        cwd: './',
                        src: 'context-install/installed.js',
                        dest: 'Gruntfile.js'
                    },
                ]
            }
        },
        replace: {
            plugin_file: {
                src: ['*.neon', '*.php', 'includes/**/*.php', 'classes/**/*.php', 'assets/**/*.css', 'assets/**/*.js', 'languages/**/*.po',],
                overwrite: true,
                replacements: [
                    {
                        from: "context plugin",
                        to: "<%= pkg.plugin_name.toLocaleLowerCase() %>"
                    },
                    {
                        from: "context_plugin",
                        to: "<%= pkg.plugin_name.toLocaleLowerCase().replace(/[^a-z0-9]/gi, '_' ) %>"
                    },
                    {
                        from: "Context_Plugin",
                        to: "<%= pkg.plugin_name.replace(/[^a-z0-9]/gi, '_' ) %>"
                    },
                    {
                        from: "Context Plugin",
                        to: "<%= pkg.plugin_name %>"
                    },
                    {
                        from: "%description%",
                        to: "<%= pkg.description %>"
                    },
                    {
                        from: "%author%",
                        to: "<%= pkg.author %>"
                    },
                    {
                        from: "%author_url%",
                        to: "<%= pkg.author_url %>"
                    },
                    {
                        from: "%plugin_url%",
                        to: "<%= pkg.plugin_url %>"
                    },
                    {
                        from: "1.0.0",
                        to: "<%= pkg.version %>"
                    },
                    {
                        from: "context-plugin",
                        to: "<%= pkg.plugin_name.toLocaleLowerCase().replace(/[^a-z0-9]/gi, '-' ) %>"
                    },
                    {
                        from: "%textdomain%",
                        to: "<%= pkg.plugin_name.toLocaleLowerCase().replace(/[^a-z0-9]/gi, '-' ) %>"
                    },
                    {
                        from: 'CNTXT',
                        to: "<%= pkg.prefix %>"
                    }
                ]
            }
        },
        clean: {
            installer: ["context-install/**"],
        }
    });

    //load modules
    grunt.loadNpmTasks('grunt-shell');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-git');
    grunt.loadNpmTasks('grunt-text-replace');
    grunt.loadNpmTasks('grunt-contrib-clean');

    //register default task
    grunt.registerTask('default', ['gitclone', 'copy', 'clean', 'replace']);

};
