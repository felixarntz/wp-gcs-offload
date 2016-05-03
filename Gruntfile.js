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
			media_library: [
				'assets/media-library.min.js',
				'assets/media-library.min.css'
			],
			attachment_edit: [
				'assets/attachment-edit.min.js',
				'assets/attachment-edit.min.css'
			],
			manage_gcs: [
				'assets/manage-gcs.min.js',
				'assets/manage-gcs.min.css'
			]
		},

		jshint: {
			options: {
				jshintrc: 'assets/.jshintrc'
			},
			media_library: {
				src: [
					'assets/media-library.js'
				]
			},
			attachment_edit: {
				src: [
					'assets/attachment-edit.js'
				]
			},
			manage_gcs: {
				src: [
					'assets/manage-gcs.js'
				]
			}
		},

		uglify: {
			options: {
				preserveComments: 'some',
				report: 'min'
			},
			media_library: {
				src: 'assets/media-library.js',
				dest: 'assets/media-library.min.js'
			},
			attachment_edit: {
				src: 'assets/attachment-edit.js',
				dest: 'assets/attachment-edit.min.js'
			},
			manage_gcs: {
				src: 'assets/manage-gcs.js',
				dest: 'assets/manage-gcs.min.js'
			}
		},

		cssmin: {
			options: {
				compatibility: 'ie8',
				keepSpecialComments: '*',
				noAdvanced: true
			},
			media_library: {
				files: {
					'assets/media-library.min.css': 'assets/media-library.css'
				}
			},
			attachment_edit: {
				files: {
					'assets/attachment-edit.min.css': 'assets/attachment-edit.css'
				}
			},
			manage_gcs: {
				files: {
					'assets/manage-gcs.min.css': 'assets/manage-gcs.css'
				}
			}
		},

		usebanner: {
			options: {
				position: 'top',
				banner: '<%= banner %>'
			},
			media_library: {
				src: [
					'assets/media-library.min.js',
					'assets/media-library.min.css'
				]
			},
			attachment_edit: {
				src: [
					'assets/attachment-edit.min.js',
					'assets/attachment-edit.min.css'
				]
			},
			manage_gcs: {
				src: [
					'assets/manage-gcs.min.js',
					'assets/manage-gcs.min.css'
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

	grunt.registerTask('media_library', [
		'clean:media_library',
		'jshint:media_library',
		'uglify:media_library',
		'cssmin:media_library'
	]);

	grunt.registerTask('attachment_edit', [
		'clean:attachment_edit',
		'jshint:attachment_edit',
		'uglify:attachment_edit',
		'cssmin:attachment_edit'
	]);

	grunt.registerTask('manage_gcs', [
		'clean:manage_gcs',
		'jshint:manage_gcs',
		'uglify:manage_gcs',
		'cssmin:manage_gcs'
	]);

	grunt.registerTask('plugin', [
		'replace:version',
		'replace:header'
	]);

	grunt.registerTask('default', [
		'media_library',
		'attachment_edit',
		'manage_gcs'
	]);

	grunt.registerTask('build', [
		'media_library',
		'attachment_edit',
		'manage_gcs',
		'plugin'
	]);
};
