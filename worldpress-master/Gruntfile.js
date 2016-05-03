module.exports = function(grunt) {
	grunt.initConfig({
		uglify:{
			options:{
				preserveComments:false,
			},
			minifyJS: {
				files: {
					'js/script.min.js':'js/script.js'
				}
			}
		},
		less: {
  			development: {
    			options: {
      				paths: ["less/"]
    			},
    		files: {
      			"style.css": "less/main.less"
    			}
    		}
  		},
  		concat: {
  			options: {
      			separator: ';',
    		},
		    dist: {
		      src: ['js/bootstrap.min.js', 'js/jslide.min.js', 'js/script.js'],
		      dest: 'js/script.js',
		    }
  		},
  		watch: {
  			less:{
  				files:['less/*.less'],
  				tasks:'less',
  			}
  		}
	});
	 grunt.loadNpmTasks('grunt-contrib-uglify');
	 grunt.loadNpmTasks('grunt-contrib-less');
	 grunt.loadNpmTasks('grunt-contrib-watch');
	 grunt.loadNpmTasks('grunt-contrib-cssmin');
	 grunt.loadNpmTasks('grunt-contrib-concat');
	 grunt.registerTask('default',['concat','uglify','less']);
}


