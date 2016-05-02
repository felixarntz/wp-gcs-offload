'use strict';
module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		banner:			'/*!\n' +
						' * <%= pkg.name %> version <%= pkg.version %>\n' +
						' * \n' +
						' * <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
						' */',
		pluginheader:	'/*\n' +
						'Plugin Name: WP GCS Offload\n' +
						'Plugin URI: <%= pkg.homepage %>\n' +
						'Description: <%= pkg.description %>\n' +
						'Version: <%= pkg.version %>\n' +
						'Author: <%= pkg.author.name %>\n' +
						'Author URI: <%= pkg.author.url %>\n' +
						'License: <%= pkg.license.name %>\n' +
						'License URI: <%= pkg.license.url %>\n' +
						'Text Domain: wp-gcs-offload\n' +
						'Tags: <%= pkg.keywords.join(", ") %>\n' +
						'*/',
		fileheader:		'/**\n' +
						' * @package WPGCSOffload\n' +
						' * @version <%= pkg.version %>\n' +
						' * @author <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
						' */',

		clean: {
			admin: [
				'assets/admin.min.js',
				'assets/admin.min.css'
			]
		},

		jshint: {
			options: {
				jshintrc: 'assets/.jshintrc'
			},
			admin: {
				src: [
					'assets/admin.js'
				]
			}
		},

		uglify: {
			options: {
				preserveComments: 'some',
				report: 'min'
			},
			admin: {
				src: 'assets/admin.js',
				dest: 'assets/admin.min.js'
			}
		},

		cssmin: {
			options: {
				compatibility: 'ie8',
				keepSpecialComments: '*',
				noAdvanced: true
			},
			admin: {
				files: {
					'assets/admin.min.css': 'assets/admin.css'
				}
			}
		},

		usebanner: {
			options: {
				position: 'top',
				banner: '<%= banner %>'
			},
			admin: {
				src: [
					'assets/admin.min.js',
					'assets/admin.min.css'
				]
			}
		},

		replace: {
			header: {
				src: [
					'wp-gcs-offload.php'
				],
				overwrite: true,
				replacements: [{
					from: /((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/,
					to: '<%= pluginheader %>'
				}]
			},
			version: {
				src: [
					'wp-gcs-offload.php',
					'inc/**/*.php'
				],
				overwrite: true,
				replacements: [{
					from: /\/\*\*\s+\*\s@package\s[^*]+\s+\*\s@version\s[^*]+\s+\*\s@author\s[^*]+\s\*\//,
					to: '<%= fileheader %>'
				}]
			}
		}

 	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-banner');
	grunt.loadNpmTasks('grunt-text-replace');

	grunt.registerTask('admin', [
		'clean:admin',
		'jshint:admin',
		'uglify:admin',
		'cssmin:admin'
	]);

	grunt.registerTask('plugin', [
		'replace:version',
		'replace:header'
	]);

	grunt.registerTask('default', [
		'admin'
	]);

	grunt.registerTask('build', [
		'admin',
		'plugin'
	]);
};
