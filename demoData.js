/*jshint node:true */
"use strict";

var keywords = [];
var cities = [];
var vehicles = [];
var year = 2014;

// Tables
var tabelPost = "";
var tabelMission = "";
var tabelVehicles = "";
var tabelMissionHasVehicle = "";

// 'post_title' => 'Wasserschaden',
//         'post_content' => '',
//         'post_status' => 'publish',
//         'post_date' => date('2013-08-06 22:00'),
//         'post_date_gmt' => date('2013-08-06 22:00'),
//         'post_author' => 1,
//         'post_type' => 'mission',
//         'comment_status' => 'closed',
//         'ping_status' => 'closed'

var insertTemplate = function(tableName){

};

var randomDate = function(start, end) {
    return new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
};

randomDate(new Date(year, 0, 1), new Date(year, 11, 31));

// INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
//     [INTO] tbl_name [(col_name,...)]
//     VALUES ({expr | DEFAULT},...),(...),...
