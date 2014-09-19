/*
Navicat SQLite Data Transfer

Source Server         : areas
Source Server Version : 30714
Source Host           : :0

Target Server Type    : SQLite
Target Server Version : 30714
File Encoding         : 65001

Date: 2014-09-19 01:47:13
*/

PRAGMA foreign_keys = OFF;

-- ----------------------------
-- Table structure for areas
-- ----------------------------
DROP TABLE IF EXISTS "main"."areas";
CREATE TABLE "areas" (
"itemid"  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
"cityname"  TEXT,
"citycode"  INTEGER,
"parentid"  INTEGER,
"nodelevel"  INTEGER,
"nodepath"  TEXT,
"citycate"  TEXT
);
