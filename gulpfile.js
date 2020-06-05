"use strict";
// Load plugins
const gulp = require('gulp');

//PHP Code Sniffer
gulp.task('phpcs', () => {
    var exec = require('child_process').exec;

    return exec('./vendor/bin/phpcs --standard=PSR2 --warning-severity=0 src tests', function(err, stdout) {
        if (stdout) {
            const error = new Error(stdout);
            gulp.emit('error', error);
        }
    });
});

gulp.task('pre-commit', ['phpcs']);

// Default task
gulp.task('default', []);
