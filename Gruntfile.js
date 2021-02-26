module.exports = function (a) {
	a.initConfig({
		pkg: a.file.readJSON("package.json"),
		addtextdomain: {
			options: {textdomain: "seriously-simple-stats"},
			update_all_domains: {
				options: {updateDomains: true},
				src: ["*.php", "**/*.php", "!.git/**/*", "!bin/**/*", "!node_modules/**/*", "!tests/**/*"]
			}
		},
		wp_readme_to_markdown: {your_target: {files: {"README.md": "readme.txt"}}},
		makepot: {
			target: {
				options: {
					domainPath: "/languages",
					exclude: [".git/*", "bin/*", "node_modules/*", "tests/*"],
					mainFile: "seriously-simple-stats.php",
					potFilename: "seriously-simple-stats.pot",
					potHeaders: {poedit: true, "x-poedit-keywordslist": true},
					type: "wp-plugin",
					updateTimestamp: true
				}
			}
		},
		uglify: {
			dev: {
				files: [{
					expand: true,
					src: ['assets/js/*.js', '!assets/js/*.min.js'],
					dest: 'assets/js',
					cwd: '.',
					rename: function (dst, src) {
						// To keep the source js files and make new files as `*.min.js`:
						// return src.replace('.js', '.min.js');
						// Or to override to src:
						// return src;
						return src.replace('.js', '.min.js');
					}
				}]
			}
		},
		cssmin: {
			target: {
				files: [{
					expand: true,
					cwd: 'assets/css',
					src: ['*.css', '!*.min.css'],
					dest: 'assets/css',
					ext: '.min.css'
				}]
			}
		},
		less: {
			target: {
				options: {
					compress: true,
					optimization: 1,
					sourceMap: true,
					sourceMapFilename: 'admin.css.map'
				},
				files: [{
					sourceMap: true,
					expand: true,
					compress: true,
					cwd: 'assets/css',
					src: ['*.less'],
					dest: 'assets/css',
					ext: '.css'
				}]
			}
		},
		watch: {
			css: {
				files: '**/*.less',
				tasks: ['less'],
				options: {
					livereload: true,
				},
			},
		},
		build: {
			tasks: ['less', 'uglify']
		}
	});
	a.loadNpmTasks('grunt-contrib-less');
	a.loadNpmTasks('grunt-contrib-uglify');
	a.loadNpmTasks('grunt-contrib-watch');

	a.registerTask('default', ['less', 'uglify']);

	a.util.linefeed = "\n"
};
