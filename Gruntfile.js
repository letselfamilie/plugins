/**
 * Created by Polina Mahur on 26.03.2019. .
 */

module.exports = function(grunt) {
    var config = {
        pkg: grunt.file.readJSON('package.json'),

        browserify:     {
            options:      {
                transform:  [ require('brfs') ],
                browserifyOptions: {
                    basedir: "forum/js/"
                }
            },

            categories: {
                src:        'forum/js/categories.js',
                dest:       'forum/js/compiled/categories.js'
            },

            posts: {
                src:        'forum/js/posts.js',
                dest:       'forum/js/compiled/posts.js'
            },
            topics: {
                src:        'forum/js/topics.js',
                dest:       'forum/js/compiled/topics.js'
            },
            chat: {
                src:        'chat/js/chat.js',
                dest:       'chat/js/compiled/chat.js'
            }
        }
    };

    var watchDebug = {
        options: {
            'no-beep': true
        },
        scripts: {
            files: ['forum/js/*.js','chat/js/*.js', 'forum/js/ejs_templates/*.ejs', 'chat/js/ejs_templates/*.ejs'],
            tasks: ['browserify:categories', 'browserify:topics', 'browserify:posts', 'browserify:chat']
        }
    };

    config.watch = watchDebug;
    grunt.initConfig(config);

    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('default',
        [
            'browserify:categories',
            'browserify:posts',
            'browserify:topics',
            'browserify:chat'
        ]
    );

};