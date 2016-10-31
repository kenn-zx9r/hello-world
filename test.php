<?php
// Here your code !
// https://paiza.io/projects/b83_lKGBwfLzDeH5ECQFTw

class JqGridDataManager {

    public static $a = [
    	"jqgrid_response" => [
    		"userdata" => [
    			"result"		=> true,
    			"error_code"	=> 0,
    			"error"			=> ""
    //			records: 0
    		],
    		"page"		=> 1,
    		"total"		=> 1,
    		"records"	=> 0,
    		"rows"		=> []
    	],
    
    	"init_status" => [
    		"lastSelect" => [
    			"request"			=> null,
    			"count_sql"			=> null,
    			"count_params"		=> null,
    			"select_sql"		=> null,	//offset ... を除いたselect文が格納される
    			"select_pager_sql"	=> null,	//offset ... の句が格納される
    			"select_params"		=> null,	//bind変数の配列が格納される
    			"response"			=> null
    		],
    		"col_model" => null
    	]
	
    ];
    
	//SQLで offset limit 句が使用可能なデータベースの名称一覧
	const ENABLE_OFFSET_DBNAMES = [
		"PostgreSQL"
	];

	const DEFAULTS = [
		"db" => [
			"database_name" => "Oracle"
		],
	    "name" => "tom",
	    "style" => [
	        "padding" => "10px",
	        "border" => [
	            "top" => [
	                "color" => "red",
	                "width" => 2
	            ],
	            "bottom" => [
	                "width" => 3
	            ]
	        ]
	    ]
	];

	public $settings;

	function __construct($settings) {
		$this->settings = array_replace_recursive(JqGridDataManager::DEFAULTS, $settings);
		
		//SQLで offset limit 句が使用可能フラグを設定
		$this->settings["enable_offset"] = in_array($this->settings["db"]["database_name"], JqGridDataManager::ENABLE_OFFSET_DBNAMES);
	}
}



$settings = [
    "name" => "jim",
    "style" => [
        "border" => [
            "top" => [
                "color" => "blue"
            ],
            "left" => [
                "color" => "green"
            ]
        ]
    ]
];

$jqGridDataManager = new JqGridDataManager($settings);

var_dump(JqGridDataManager::$a);
var_dump(JqGridDataManager::ENABLE_OFFSET_DBNAMES);
var_dump($jqGridDataManager->settings);
//var_dump(array_merge($defaults, $settings));
//var_dump(array_replace_recursive($defaults, $settings));
//phpinfo();
//var_dump(json_encode(JqGridDataManager::$a));
?>
