module.exports = function(grunt) {
	"use strict";

    var path = require('path');
    var fs = require('fs');
    // var versioning = require('versioning');

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		
	});

    grunt.registerTask('default', ['precommit']);

    grunt.registerTask('precommit', '', function(){
        var packageJson = grunt.file.readJSON('package.json');
        var einsatzverwaltung = readFile('einsatzverwaltung.php');
        var readme = readFile('Readme.md');


        console.log(packageJson+'\n'+einsatzverwaltung+'\n'+readme);
    });

    var readFile = function(file){
        var ext = path.extname(file);
        if(ext === '.php'){
            getVersion(file);
            return 'php';
        }else if(ext === '.md'){
            getVersion(file);
            return 'md';
        }else{
            return ext+" not supported";
        }
    };

    var updateVersion = function(file){
        var php = fs.readFileSync(file,'utf8');
        var regex = /\d*.\.\d*.\.\d*/g;

        var version = regex.exec(php);
        console.log('version: '+version);
        return version;
    };

    var incrementVersion = function(oldVersion){

        var version = oldVersion.split('.');
        var versionNumber = parseInt(version[2], 10);
        return version[0]+'.'+version[1]+'.'+versionNumber++;
    };
};


// module.exports = function versioning() {

//     exports.incrementVersion = function(){

//         var packageJson = grunt.file.readJSON();
//         var einsatzverwaltung;
//         var readme;



//     };


//     return exports;
// };
