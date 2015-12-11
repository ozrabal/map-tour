var gulp = require('gulp'),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename'),
    minifyCSS = require('gulp-minify-css'),
    autoprefixer = require('gulp-autoprefixer');

gulp.task('default', function(){
    gulp.start( 'scripts', 'styles');
});

gulp.task('scripts',  function() {
    return  gulp.src(['js/*.js', '!js/*.min.js'])
	.pipe(uglify())
	.pipe(rename({suffix: '.min'}))
	.pipe(gulp.dest('js'));
});

gulp.task('styles', function(){
    return  gulp.src(['css/*.css', '!css/*.min.css'])
	.pipe(autoprefixer({browsers: ['> 1%']}))
	.pipe(minifyCSS({advanced: false}))
	.pipe(rename({suffix: '.min'}))
	.pipe(gulp.dest('css'));
});