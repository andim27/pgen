var dest = require('gulp-dest');
const gulp = require('gulp');
const phpMinify = require('@aquafadas/gulp-php-minify');
var rename = require('gulp-rename');
var uglify = require('gulp-uglify');
var pump = require('pump');
var htmlmin = require('gulp-htmlmin');
 
gulp.task('minify:php', () => gulp.src('params/plug.php', {read: false})
  .pipe(phpMinify())
  .pipe(gulp.dest('params/plug.job.php'))
);

gulp.task('chart', function (cb) {
  pump([
        gulp.src('js/chart.js'),
        uglify(),
	rename('chart.job.js'),
        gulp.dest('public/js/')
    ],
    cb
  );
});

gulp.task('index-minify', function() {
  return gulp.src('index.html')
    .pipe(htmlmin({collapseWhitespace: true}))
    .pipe(gulp.dest('public/'));
});

gulp.task('main', function (cb) {
  pump([
        gulp.src('js/main.js'),
        uglify(),
	rename('main.job.js'),
	gulp.dest('public/js/')
    ],
    cb
  );
});

gulp.task('public', ['chart', 'main','index-minify']);