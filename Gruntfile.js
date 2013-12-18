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
        var version = incrementVersion(packageJson);

        // stream = fs.createReadStream(path.join('einsatzverwaltung.php'), {encoding: 'utf8'});
        // stream.on('readable', read);
        // stream.once('end', function() {
        //     console.log('stream ended');
        // });
        // updateVersion(packageJson, version);
        updateVersion(einsatzverwaltung, version);
        // updateVersion(readme, version);

        // console.log(packageJson+'\n'+einsatzverwaltung+'\n'+readme);
    });

    // var read = function() {
    //     var buf;
    //     while (buf = stream.read()) {
    //         console.log('Read from the file:', buf);
    //     }
    // };

    var readFile = function(file){
        return fs.readFileSync(file,'utf8');
    };

    var writeFile = function(filePath, content){
        fs.writeFileSync(fileName, content, 'utf8');
    };

    var updateVersion = function(file, filePath, newVersion){
        var regex = /\d*.\.\d*.\.\d*/;





        var oldVersion = regex.exec(file);
        console.log('old version: '+oldVersion);
        console.log('new version: '+newVersion);

        file = file.replace(regex, newVersion);

        console.log(file);

        // return version;
    };

    var incrementVersion = function(file){

        var version = file.version.split('.');
        var versionNumber = parseInt(version[2], 10);
        versionNumber++;
        return version[0]+'.'+version[1]+'.'+versionNumber;
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
