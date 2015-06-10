var gulp = require('gulp');
var Q = require('q');

var clean = require('gulp-clean');
var less = require('gulp-less');
var LessPluginCleanCSS = require('less-plugin-clean-css');
var LessPluginAutoPrefix = require('less-plugin-autoprefix');
var imagemin = require('gulp-imagemin');
var source = require('vinyl-source-stream');
var buffer = require('vinyl-buffer');
var uglify = require('gulp-uglify');
var sourcemaps = require('gulp-sourcemaps');
var gutil = require('gulp-util');
var browserify = require('browserify');
var coffeeify = require('coffeeify');


/* meta tasks */

gulp.task('default', ['watch']);
gulp.task('publish', ['build:style', 'build:script', 'publish:thirdparty', 'publish:images']);
gulp.task('clean', ['clean:style', 'clean:thirdparty', 'clean:images']);


gulp.task('watch', function() {
  gulp.watch('./assets/img/**/*', ['publish:images']);
  gulp.watch('./assets/js/**/*', ['build:script']);
  return gulp.watch('./assets/style/*.less', ['build:style']);  // Watch all the .less files, then run the less task
});


/* source related tasks */

gulp.task('publish:images', ['clean:images'], function() {
  return gulp.src('assets/img/**/*')
    .pipe(imagemin({
      progressive: true
    }))
    .pipe(gulp.dest('./public/img'));
});

gulp.task('build:style', ['clean:style'], function() {
  return gulp.src('assets/style/courseadvisor.less')
    .pipe(less({
      plugins: [
        new LessPluginCleanCSS({ advanced: true }),
        new LessPluginAutoPrefix({ browsers: ["last 2 versions"] })
      ]
    }))
    .pipe(gulp.dest('./public/css'));
});

gulp.task('build:script', function () {
  // set up the browserify instance on a task basis
  var b = browserify({
    entries: 'assets/js/app.js',
    transform: [coffeeify],
    extensions: ['.coffee'],
    debug: true
  });

  return b.bundle()
    .pipe(source('js/app.js'))
    .pipe(buffer())
    .pipe(sourcemaps.init({loadMaps: true}))
    .pipe(uglify())
    .on('error', gutil.log)
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('public/'));
});

/* 3rd party assets */

gulp.task('publish:thirdparty', ['clean:thirdparty'], function() {
  var collector = task_collector();

  gulp.src('assets/bower_components/font-awesome/css/font-awesome.min.css')
    .pipe(gulp.dest('./public/css'))
    .on('finish', collector());

  gulp.src('assets/bower_components/font-awesome/fonts/*')
    .pipe(gulp.dest('./public/fonts'))
    .on('finish', collector());

  gulp.src('assets/bower_components/bootstrap/dist/js/bootstrap.min.js')
    .pipe(gulp.dest('./public/js/vendor'))
    .on('finish', collector());

  return collector.promise;
});


/* cleaning */

gulp.task('clean:images', function() {
  return gulp.src('public/img/**/*')
    .pipe(clean());
});

gulp.task('clean:style', function() {
  return gulp.src('public/css/courseadvisor.*', {read: false})
      .pipe(clean());
});

gulp.task('clean:thirdparty', function() {
  var collector = task_collector();

  gulp.src('public/css/font-awesome.*', {read: false})
      .pipe(clean())
      .on('finish', collector());

  gulp.src('public/fonts/*', {read: false})
      .pipe(clean())
      .on('finish', collector());

  gulp.src('public/js/vendor/*', {read: false})
      .pipe(clean())
      .on('finish', collector());

  return collector.promise;
});


/**
 *  A defer for multiple tasks
 */
var task_collector = function() {
  var callbacks = 0;
  var deferred = Q.defer();

  function collect() {
    if (--callbacks <= 0) {
      deferred.resolve();
    }
  }

  var collector = function() {
    ++callbacks;
    return collect;
  }
  collector.promise = deferred.promise;

  return collector;
};