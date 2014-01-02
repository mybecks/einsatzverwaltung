module.exports = function(grunt) {
	"use strict";

    var path = require('path');
    var fs = require('fs');
    var exec = require('child_process').exec;

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		
	});

    grunt.registerTask('default', ['precommit']);

    grunt.registerTask('precommit', '', function(){
        var packageJson = {
            content: grunt.file.readJSON('package.json'),
            filePath: path.join(__dirname, 'package.json')
        };
        var einsatzverwaltung = {
            content: readFile('einsatzverwaltung.php'),
            filePath: path.join(__dirname, 'einsatzverwaltung.php')
        };
        var readme = {
            content: readFile('Readme.md'),
            filePath: path.join(__dirname, 'Readme.md')
        };
        
        var version = incrementVersion(packageJson);

        console.log(version);

        // updateVersion(packageJson, version);
        updateVersion(einsatzverwaltung, version);
        updateVersion(readme, version);

        // console.log(packageJson+'\n'+einsatzverwaltung+'\n'+readme);
    });

    var readFile = function(file){
        return fs.readFileSync(file,'utf8');
    };

    var writeFile = function(filePath, content){
        fs.writeFileSync(filePath, content, 'utf8');
    };

    var updateVersion = function(file, newVersion){
        var regex = /\d*.\.\d*.\.\d*/;





        var oldVersion = regex.exec(file.content);
        console.log('old version: '+oldVersion);
        console.log('new version: '+newVersion);

        file.content = file.content.replace(regex, newVersion);

        console.log(file.content);

        // return version;
    };

    var incrementVersion = function(file){

        var version = file.content.version.split('.');
        var versionNumber = parseInt(version[2], 10);
        versionNumber++;

        var retVal = version[0]+'.'+version[1]+'.'+versionNumber;

        file.content['version'] = retVal;
    
        fs.writeFileSync(file.filePath, JSON.stringify(file.content, null, 4));

        return retVal;
    };

    var addFilesToGit = function(){

        exec('git add package.json && git add einsatzverwaltung.php && git add Readme.md', function(error, stdout, stderr){
            commitPackageJsonToGit();

        });

    };

    var commitFilesToGit = function(){
        exec('git commit -m "raised version"', function(error, stdout, stderr){

            if(stdout !== null){
                console.info(stdout);
            }else{
                console.info('stderr: ' + stderr);
            }

            if (error !== null) {
                console.info('exec error: ' + error);
            }
        });
    };
};
