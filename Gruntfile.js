module.exports = function(grunt) {
	'use strict';

	// Project configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		// Clean dist folder before build
		clean: {
			dist: ['dist/'],
			build: ['dist/<%= pkg.name %>']
		},

		// Copy files to dist folder
		copy: {
			build: {
				expand: true,
				src: [
					'**',
					'!node_modules/**',
					'!dist/**',
					'!.git/**',
					'!.gitignore',
					'!Gruntfile.js',
					'!package.json',
					'!package-lock.json',
					'!phpcs.xml',
					'!IMPLEMENTATION.txt',
					'!README-FINAL.txt',
					'!*.log',
					'!*.zip'
				],
				dest: 'dist/<%= pkg.name %>/'
			}
		},

		// Compress into zip file
		compress: {
			build: {
				options: {
					archive: 'dist/<%= pkg.name %>-<%= pkg.version %>.zip',
					mode: 'zip'
				},
				files: [
					{
						expand: true,
						cwd: 'dist/',
						src: ['<%= pkg.name %>/**'],
						dest: '/'
					}
				]
			}
		},

		// WordPress Coding Standards check
		phpcs: {
			application: {
				src: [
					'**/*.php',
					'!node_modules/**',
					'!dist/**',
					'!vendor/**'
				]
			},
			options: {
				bin: 'vendor/bin/phpcs',
				standard: 'WordPress',
				reportFile: 'phpcs-report.txt',
				showSniffCodes: true,
				severity: 1,
				errorSeverity: 1,
				warningSeverity: 5
			}
		},

		// Generate POT file for translations
		makepot: {
			target: {
				options: {
					cwd: '',
					domainPath: '/languages',
					exclude: [
						'node_modules/.*',
						'dist/.*',
						'vendor/.*'
					],
					mainFile: 'learndash-fluentcart.php',
					potFilename: 'learndash-fluentcart.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true,
						'report-msgid-bugs-to': 'https://wbcomdesigns.com/',
						'last-translator': 'Wbcom Designs',
						'language-team': 'Wbcom Designs <admin@wbcomdesigns.com>',
						'language': 'en_US'
					},
					type: 'wp-plugin',
					updateTimestamp: true,
					updatePoFiles: true
				}
			}
		}
	});

	// Load tasks
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.loadNpmTasks('grunt-phpcs');
	grunt.loadNpmTasks('grunt-wp-i18n');

	// Register tasks
	grunt.registerTask('default', ['build']);
	grunt.registerTask('build', [
		'clean:dist',
		'makepot',
		'copy:build',
		'compress:build',
		'clean:build'
	]);

	grunt.registerTask('zip', ['build']);
	grunt.registerTask('wpcs', ['phpcs']);
	grunt.registerTask('i18n', ['makepot']);
};
