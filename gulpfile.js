var gulp = require('gulp');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
gulp.task('default', function(){
  //var assets = useref.assets();

  return gulp.src('js/*.js')
    //.pipe(assets)
    .pipe(uglify()) // Uglifies Javascript files
    //.pipe(assets.restore())
    //.pipe(useref())
    .pipe(rename({extname: '.min.js'}))
    .pipe(gulp.dest('js'));
});