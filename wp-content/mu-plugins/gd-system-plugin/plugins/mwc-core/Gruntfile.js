/* global module, require */

module.exports = function(grunt) {

	var pkg = grunt.file.readJSON('package.json');

	grunt.initConfig({

		pkg: pkg,

		cssjanus: {
			dist: {
				options: {
					generateExactDuplicates: true,
					swapLtrRtlInUrl: false
				},
				files: [
					{
						expand: true,
						cwd: 'assets/css/',
						src: [ '*.css', '!*-rtl.css', '!*.min.css', '!*-rtl.min.css' ],
						dest: 'assets/css/',
						ext: '-rtl.css'
					}
				]
			}
		},

		cssmin: {
			options: {
				processImport: false,
				roundingPrecision: 5,
				shorthandCompacting: false
			},
			assets: {
				expand: true,
				cwd: 'assets/css/',
				src: ['*.css', '!*.min.css'],
				dest: 'assets/css/',
				ext: '.min.css'
			}
		},

		jshint: {
			assets: {
				expand: true,
				cwd: 'assets/js/',
				src: ['*.js', '!*.min.js'],
				dest: 'assets/js/'
			},
			gruntfile: ['Gruntfile.js']
		},

		potomo: {
			files: {
				expand: true,
				cwd: 'languages/',
				src: ['*.po'],
				dest: 'languages/',
				ext: '.mo'
			}
		},

		replace: {
			version_php: {
				src: [
					pkg.name + '.php',
					'src/*.php',
					'src/**/*.php'
				],
				overwrite: true,
				replacements: [
					{
						from: /Version:(\s*?)[a-zA-Z0-9\.\-\+]+$/m,
						to: 'Version:$1' + pkg.version
					},
					{
						from: /@version(\s*?)[a-zA-Z0-9\.\-\+]+$/m,
						to: '@version$1' + pkg.version
					},
					{
						from: /@since(.*?)NEXT/mg,
						to: '@since$1' + pkg.version
					},
					{
						from: /MWC_CORE_VERSION(['"]\s*?),(\s*?['"])[a-zA-Z0-9\.\-\+]+/mg,
						to: 'MWC_CORE_VERSION$1,$2' + pkg.version
					}
				]
			}
		},

		uglify: {
			options: {
				ASCIIOnly: true
			},
			dist: {
				expand: true,
				cwd: 'assets/js/',
				src: ['*.js', '!*.min.js'],
				dest: 'assets/js/',
				ext: '.min.js'
			}
		},

		watch: {
			css: {
				files: ['assets/css/*.css', '!assets/css/*.min.css'],
				tasks: ['cssmin'],
				options: {
					interrupt: true
				}
			},
			scripts: {
				files: ['Gruntfile.js', 'assets/js/*.js', '!/assets/js/*.min.js'],
				tasks: ['jshint', 'uglify'],
				options: {
					interrupt: true
				}
			},
		},

		shell: {
			json2po: 'npm run json2po',
			npm_audit: 'npm audit fix'
		}

	});

	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-cssjanus');
	grunt.loadNpmTasks('grunt-potomo');
	grunt.loadNpmTasks('grunt-shell');
	grunt.loadNpmTasks('grunt-text-replace');

	grunt.registerTask('default',   ['shell:npm_audit', 'cssjanus', 'cssmin', 'jshint', 'uglify']);
	grunt.registerTask('json2pomo', ['shell:json2po', 'potomo']);
	grunt.registerTask('version',   ['replace']);

};
