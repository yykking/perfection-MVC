<?php
//解决一个名空间对应多个路径的方式：1）键值对调
// return [
// 	'index\\controller'     =>'app/index/controller',
// 	'framework'				=>'vendor/csl/framework/src',
// 	'framework'				=>'vendor/zhicong/framework/src'

// ];

return [
	'app/index/controller'			=>'index\\controller',
	'app/index/model'				=>'index\\model',
	'vendor/csl/framework/src'		=>'framework',
	'vendor/zhicong/framework/src'  =>'framework'
];