<?php
/*
 * 
 * ファイル名：JqGridDataManager.js
 * 摘要：jqGridデータ管理クラス
 * 内容：jqGrid で扱うデータの各種操作を行う
 * 
 * 
 */


//Procedureにバインドされている関数の参照時エイリアスを設定
var _HRS = Procedure.HRS;
var $ = Procedure.HRS.$;

//ログ出力クラス
var myLog	= new _HRS.MyLog("JqGridDataManager.js");

/*
 * 関数名：init
 * 内容：メソッドを定義する。
 * 引数：なし
 * 戻値：なし
*/
function init()
{
	//名前空間HRSに、当ファイルで定義する関数を登録する
	$.extend(Procedure.HRS, _ME);
}

/*
 * 
 * 
 * 当ファイルで定義するPublic関数を全てここに入れる。「ME」は、「自分自身」、という意味
 * 
 * 
 */
var _ME = {
	
	//jqGridデータ管理クラス
	JqGridDataManager: JqGridDataManager

};



class JqGridDataManager
{
	const DEFAULTS => [
		"cache_record" => false,
		"db" => [
			"database_name" => "Oracle"
		]
	];
	
	//SQLで offset limit 句が使用可能なデータベースの名称一覧
	const ENABLE_OFFSET_DBNAMES = [
		"PostgreSQL"
	];

	/*
	 * Static変数
	 */
	public static $jqgrid_response = [
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
	];

	public static $init_status = [
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
	];


	public $settings;
	public $status;


	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	 * 
	 * 
	 * コンストラクタ
	 * 
	 * 
	 */
	/* =================================================================================================
	 * 
	 * クラス名：	JqGridDataManager
	 * 内容：		
	 * 引数：		settings: 設定情報を全て含むオブジェクト
	 * 				
	 * 			settings = {
	 * 				db			...	(object) データベースオブジェクト(TenantDatabase / SharedDatabase)
	 * 
	 * 				makeSelectSql
	 * 							...	(function(request, options)) 
	 * 								当クラスのメソッド[select]でjqGridに表示するデータを取得するが、
	 * 								その際、データを取得するために使用するSQLを生成する関数となる。
	 * 								この関数の戻り値オブジェクトに格納されたsql文とバインドパラメータ
	 * 								を使用して、データベースへの問い合わせを行う。
	 * 									※当関数で生成されたSQLは、実行時にrequestに含まれているjqGrid
	 * 									  標準のパラメータで指定されている条件及び並び順の制御が加えら
	 * 									  れた状態でデータベースに問い合わせされる。
	 * 										
	 * 										select
	 * 											tg.*
	 * 										from
	 * 											( [当関数で生成されたSQL] ) tg
	 * 										where
	 * 											[jqGrid標準パラメータより生成した条件]
	 * 										order by
	 * 											[jqGrid標準パラメータより生成したソート順]
	 * 										;
	 * 									  上記のjqGrid標準パラメータによる制御も含めて、完全なSQLを独自
	 * 									  に生成する必要がある場合は、別メソッド[makeAbsoluteSelectSql]
	 * 									  を使用すること。
	 * 								
	 * 								<<関数の仕様>>
	 * 								引数:	request	... HTTPリクエストパラメータ。
	 * 													当クラスのメソッド[select]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 										options	... 任意のオプションを任意の形式で指定する。
	 * 													当クラスのメソッド[select]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 													当[makeSelectSql]関数の内部仕様により、利用者側で自由に
	 * 													使用できるもの。
	 * 								戻り値:	(object) {
	 * 									sql		... (string)データベース問い合わせを行うためのSQL文字列を
	 * 												設定する。
	 * 									params	... (Array)該当のSQLでバインド変数を使用するのであれば、
	 * 												バインド変数を以下の形式のオブジェクト配列で設定する。
	 * 												（バインド変数が無い場合は不要）
	 * 												※DbParameterはシリアライザブルでないためセッションに
	 * 												  保存することができないため、ここでは使用しない。
	 * 												<<バインド変数オブジェクトの形式>>
	 * 												{
	 * 													data	...	(Object)バインド変数に割り当てる任意のデータ
	 * 													type	...	(number)バインド変数の型を表すDbParameterの
	 * 																の定数。 DbParameter.TYPE_XXXX を指定。
	 * 																http://www.intra-mart.jp/apidoc/iap/apilist-ssjs/doc/platform/DbParameter/index.html
	 * 												}
	 * 								}
	 * 				defaultOrderBy
	 * 							...	(string)（任意）
	 * 								デフォルトのソート順を指定したい場合、order by句をここで指定する。
	 * 								[makeSelectSql]を使用する場合のみ適用される。
	 * 								ここで指定した句が、order by の末尾に必ず追加される。
	 * 								クライアント上のgridから指定されたソート順が優先度が高く先に指定され、
	 * 								その後でここでの指定が追加される。
	 * 									例）"tokui_id, todokesaki_id desc, seihin_cd desc"
	 * 
	 * 				makeCountSql
	 * 							...	(function(request, options)) （任意）
	 * 								当クラスのメソッド[select]でjqGridに表示するデータを取得するが、
	 * 								その際、全体のデータ件数を取得するために使用するSQLを生成する関数。
	 * 								[makeSelectSql]の結果を単純に全体のレコード件数とみなすことで問題
	 * 								なければ、当関数の指定は不要。当関数が指定されていなければ、[makeSelectSql]
	 * 								に抽出条件を一切加えない状態でデータベースに問い合わせ、その全件数
	 * 								を全体のデータ件数とする。
	 * 								例えば当関数のSQLはインデックスのついた列1行のみをSELECTしてデータ
	 * 								数をカウントするようなものにしておくと、件数取得処理のパフォーマンス
	 * 								を高くすることができる。
	 * 								SQLは、「レコード件数」（列名 "reccount")を1列戻すだけのものにすること。
	 * 								この関数の戻り値オブジェクトに格納されたsql文とバインドパラメータ
	 * 								を使用して、データベースへの問い合わせを行う。
	 * 									例）
	 * 										select
	 * 											count(tg.id)	reccount
	 * 										from
	 * 											mt_seihin
	 * 										;
	 * 								
	 * 								<<関数の仕様>>
	 * 								引数:	request	... HTTPリクエストパラメータ。
	 * 													当クラスのメソッド[select]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 										options	... 任意のオプションを任意の形式で指定する。
	 * 													当クラスのメソッド[select]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 													当[makeSelectSql]関数の内部仕様により、利用者側で自由に
	 * 													使用できるもの。
	 * 								戻り値:	(object) {
	 * 									※関数[makeSelectSql]の戻り値と同じ
	 * 								}
	 * 
	 * 				makeAbsoluteSelectSql
	 * 							...	(function(request, options)) 
	 * 								当クラスのメソッド[select]でjqGridに表示するデータを取得するが、
	 * 								その際、データを取得するために使用するSQLを生成する関数となる。
	 * 								この関数の戻り値オブジェクトに格納されたsql文とバインドパラメータ
	 * 								を使用して、データベースへの問い合わせを行う。
	 * 									※当関数で生成されたSQLは、一切加工されず、そのままの形でデータ
	 * 									  ベースに問い合わせされる。
	 * 									  jqGrid標準機能のハンドリングは一切行わないので、必要があれば
	 * 									  当関数の中で独自にハンドリングの実装を行うこと。
	 * 									  また、特に必要がなければ、別メソッド[makeSelectSql]を使用する
	 * 									  こと。
	 * 								
	 * 								<<関数の仕様>>
	 * 								引数:	request	... HTTPリクエストパラメータ。
	 * 													当クラスのメソッド[select]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 										options	... 任意のオプションを任意の形式で指定する。
	 * 													当クラスのメソッド[select]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 													当[makeAbsoluteCountSql]関数の内部仕様により、
	 * 													利用者側で自由に使用できるもの。
	 * 								戻り値:	(object) {
	 * 									※関数[makeSelectSql]の戻り値と同じ
	 * 								}
	 * 
	 * 				makeAbsoluteCountSql
	 * 							...	(function(request, options)) 
	 * 								[makeAbsoluteSelectSql]を定義した場合、当関数は必須。
	 * 								全体のレコード数を取得する完全なSQLを返す関数をここで指定する。
	 * 								この関数の戻り値オブジェクトに格納されたsql文とバインドパラメータ
	 * 								を使用して、データベースへの問い合わせを行う。
	 * 								
	 * 								<<関数の仕様>>
	 * 								引数:	request	... HTTPリクエストパラメータ。
	 * 													当クラスのメソッド[select]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 										options	... 任意のオプションを任意の形式で指定する。
	 * 													当クラスのメソッド[select]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 													当[makeAbsoluteCountSql]関数の内部仕様により、
	 * 													利用者側で自由に使用できるもの。
	 * 								戻り値:	(object) {
	 * 									※関数[makeSelectSql]の戻り値と同じ
	 * 								}
	 * 
	 * 				afterSelect	...	(function(request, response, options)) 
	 * 								当クラスのメソッド[select]でjqGridに表示するデータを取得するが、
	 * 								その最後に呼ばれる関数をここで指定する。
	 * 								クライアントへのレスポンスを返却する直前に実行される。
	 * 								当関数には、リクエストパラメータ、返却するレスポンスオブジェクト、
	 * 								等が引数で渡される。レスポンスオブジェクトは必要に応じて直接加工する
	 * 								こともできる。
	 * 								例えばクライアントに返却したい値を追加したい場合などは、この関数で
	 * 								レスポンスオブジェクトにプロパティを追加すれば良い。
	 * 								また、次回呼び出し時に使用したい値等があれば、インスタンスのステータス
	 * 								に値を追加することでセッションに値が保存され、次回呼び出し時に参照する
	 * 								ことが可能になる。この場合は当関数の中で [this.status] でステータスを
	 * 								取得し、そのステータスオブジェクトを直接加工すれば良い。
	 * 								
	 * 								<<関数の仕様>>
	 * 								引数:	request	... HTTPリクエストパラメータ。
	 * 													当クラスのメソッド[select]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 										response... [select]で生成したクライアントへのレスポンス
	 * 													オブジェクト。クライアント側のjqGridで使用する。
	 * 										options	... 任意のオプションを任意の形式で指定する。
	 * 													当クラスのメソッド[select]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 													当[afterSelect]関数の内部仕様により、利用者側で
	 * 													自由に使用できるもの。
	 * 								戻り値:	無し
	 * 
	 * 				onEdit		...	(function(request, options)) 
	 * 								当クラスのメソッド[edit]が呼ばれた際に呼ばれる関数。
	 * 								この関数の戻り値はクライアントにそのまま返却される。
	 * 								
	 * 								<<関数の仕様>>
	 * 								引数:	request	... HTTPリクエストパラメータ。
	 * 													当クラスのメソッド[edit]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 										options	... 任意のオプションを任意の形式で指定する。
	 * 													当クラスのメソッド[edit]に引き渡された同名
	 * 													のパラメータがそのまま引き渡される。
	 * 													当[onEdit]関数の内部仕様により、利用者側で自由に
	 * 													使用できるもの。
	 * 								戻り値:	(object) クライアントに返却するデータ
	 * 
	 * 				cacheRecord	...	(boolean) 取得したレコードをセッションに保存するか否かを指定する。
	 * 								(default: false)
	 * 				
	 * 				customMethods
	 * 							...	( { function(request) , ... } ) ※関数を含むオブジェクト
	 * 								当クラスのインスタンスに追加する独自メソッドをプロパティとして持つ
	 * 								オブジェクトを指定する。このオブジェクトのそれぞれのプロパティが
	 * 								インスタンスメソッドとして登録される。
	 * 								これらの関数はインスタンスのメソッドとして呼び出すことが可能となる。
	 * 								
	 * 								例）
	 * 									{
	 * 										methodA: function(request){
	 * 											...
	 * 										},
	 * 										methodB: function(request){
	 * 											...
	 * 										},
	 * 										...
	 * 										methodZ: function(request){
	 * 											...
	 * 										}
	 * 									}
	 
	 * 								<<関数の仕様>>
	 * 								引数:	request	... HTTPリクエストパラメータ。
	 * 								戻り値:	(object) クライアントに返却するデータ
	 * 
	 * 			}
	 * 
	 * 戻値：
	 * 
	 * =================================================================================================
	*/
	function __construct($settings) {
		const MYNAME = "JqGridDataManager";
		//myLog.out(_HRS.LOG_I, MYNAME);

		/*
		 * インスタンス変数
		 */
		$this->settings = array_replace_recursive(JqGridDataManager::DEFAULTS, $settings);
		
		//SQLで offset limit 句が使用可能フラグを設定
		$this->settings["enable_offset"] = in_array($this->settings["db"]["database_name"], JqGridDataManager::ENABLE_OFFSET_DBNAMES);
		
		//ステータス
		//	このインスタンス変数は、jqGridData.jsにてセッションに保存される。
		//	また、同じマネージャが呼び出された際にはセッションからステータス
		//	を取得し、インスタンスに再度セットされる。この仕組みにより、
		//	ステータスはセッションを通じて保持される。
		$this->status = array_replace_recursive([], JqGridDataManager::$init_status);


//#################
//そもそも、これ、必要か？？
//#################

		//カスタムメソッドの登録
		if(isset($settings["custom_methods"])){
			for(var name in settings.customMethods){
				this[name] = settings.customMethods[name];
			}
		}
	}

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * 
 * 
 * クラスメソッド
 * 
 * 
 */

	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	 * 
	 * 
	 * インスタンスメソッド
	 * 
	 * 
	 */

	/* =============================================================================================
	 * 
	 * 関数名：	select
	 * 内容：	makeSelectSql関数で生成されたsqlを発行して該当のデータを取得し、jqGrid用のレスポンス
	 * 			オブジェクトに設定して返却する
	 * 
	 * 引数：	request	... HTTPリクエストパラメータ。
	 * 			options	... [makeSelectSql]関数に引き渡す任意のオプションを任意の形式で指定する。
	 * 			
	 * 戻値：	jqGridレスポンスオブジェクト
	 * 			{
	 * 				userdata: {			... ユーザ定義データ
	 * 					result,			... 問い合わせ結果が正常か否か(正常:true/異常:false)
	 * 					errorCode,		... 問い合わせ結果が以上の場合のエラーコード
	 * 					error,			... 問い合わせ結果が以上の場合のエラーメッセージ
	 * 					records			... 問い合わせ結果のレコード件数
	 * 				},
	 * 				page,				... クライアントのjqGrid上の現在のページ
	 * 				total,				... クライアントのjqGrid上のページ数の合計
	 * 				records,			... 全体のレコード数
	 * 				rows:[]				... クライアントのjqGrid上の現在のページに該当するデータ
	 * 			}
	 * 
	 * =============================================================================================
	 */
	public function select(request, options){
		var nowSelect = {};
		nowSelect.request = getRequestParams(request);
		var st = this.settings;
		var db = st.db;
		var sql, base, params;
		var selSql = {};
		var cntSql = {};
		var res = $.extend({}, JqGridDataManager.jqGridResponse);
		
		//クライアント側でjqGridSelectDialogを使用しており、
		//autoRefresh処理のリクエストがあるなら、ページングは不要
		var noPager = (request.jqGridSelectDialogAutoRefresh == "true");
		
		//jqGrid標準のパラメータを取得
		var page = Math.floor(Number(request.page));	//何ページ目を表示するか
		var rows = Number(request.rows);				//ページャの１ページ当りレコード数

		//colModelをステータスに保存
		if(request.colModel){
			this.status.colModel = ImJson.parseJSON(request.colModel);
		}
		
		//初回読み込み時で空データ返却が要求されていたなら、空データ返却
		if(request.getEmpty == "true"){
			return JqGridDataManager.jqGridResponse;
		}

		
		//jqGrid自動ハンドリングを行うSQLの生成
		if(typeof st.makeSelectSql == "function"){
			base = st.makeSelectSql.call(this, request, options);
			
			//検索条件の生成
			var sqlWhere = this.makeJqGridWhere(request, "tg");
			
			//ソート順の生成
			var sqlOrderBy = this.makeJqGridOrderBy(request, "tg");
			if(st.defaultOrderBy){
				//デフォルトソート順があるなら、末尾に追加
				var o = st.defaultOrderBy.split(",");
				for(var i=0,l=o.length; i<l; i++){
					sqlOrderBy += ((sqlOrderBy)? "," : "") + "tg." + o[i].trim();
				}
			}
			
			//jqGrid標準条件を含めたSQLの生成
			if(sqlWhere || sqlOrderBy){
				if(st.enableOffset){
					//offset limit句が使用可能なDBの場合（PostgreSQL等）
					selSql.sql = ""
						+ "	select "
						+ "		tg.* "
						+ "	from "
						+ "		( " + base.sql + " ) tg "
						;
					if(sqlWhere){
						selSql.sql += ""
						+ "	where "
						+ "		" + sqlWhere
						;
					}
					if(sqlOrderBy){
						selSql.sql += ""
						+ "	order by "
						+ "		" + sqlOrderBy
						;
					}
				}else{
					//offset limit句が使用できないDBの場合（Oracle等）
					var rownumber = "";
					if( !noPager ){
						if(sqlOrderBy){
							//ページャが必要でソート順の指定がある場合
							rownumber += ""
								+ "	row_number() over( "
								+ "		order by " + sqlOrderBy
								+ "	) row_n, "
								;
						}else{
							//ページャが必要だけどソート順の指定が無い場合
							rownumber += " rownum row_n, ";
						}
					}
					
					selSql.sql = ""
						+ "	select /*+ FIRST_ROWS */ "
						+ "		" + rownumber
						+ "		tg.* "
						+ "	from "
						+ "		( " + base.sql + " ) tg "
						;
					if(sqlWhere){
						selSql.sql += ""
						+ "	where "
						+ "		" + sqlWhere
						;
					}
					if(noPager && sqlOrderBy){
						//ページャが不要だけどソート順の指定がある場合
						selSql.sql += ""
						+ "	order by "
						+ "		" + sqlOrderBy
						;
					}
				}
			}else{
				selSql.sql = base.sql;
			}
			
			selSql.params = base.params;

			
			if(typeof st.makeCountSql == "function"){
				//件数取得SQL関数があれば、取得
				cntSql = st.makeCountSql.call(this, request, options);
				
			}else{
				//件数取得SQL関数がなければ、ベースのSQLを元に編集
				cntSql.sql = ""
					+ "	select "
					+ "		count(*)	reccount "
					+ "	from "
					+ "		( " + base.sql + " ) tg "
					;
				if(sqlWhere){
					cntSql.sql += ""
					+ "	where "
					+ "		" + sqlWhere
					;
				}
				cntSql.params = base.params;
			}
		}
		
		//独自ロジックによる完全なSQLの生成
		if(typeof st.makeAbsoluteSelectSql == "function"){
			//レコード選択用SQL生成
			selSql = st.makeAbsoluteSelectSql.call(this, request, options);
			
			//全体のレコード件数取得用SQL生成
			if(typeof st.makeAbsoluteCountSql != "function"){
				throw "Method [makeAbsoluteCountSql] is not defined.";
			}
			cntSql = st.makeAbsoluteCountSql.call(this, request, options);
		}
		
		//全体のレコード件数取得用SQLの実行
		nowSelect.countSql = cntSql.sql;
		nowSelect.countParams = cntSql.params;
		params = this.makeDbParameters(cntSql.params);
		var result = db.select(cntSql.sql, params);
		if (result.error) myLog.debug(result.sql, params);
		if (result.error) throw result;
		
		//全体のレコード件数を取得
		var reccount = result.data[0].reccount;
		if(result.countRow != 1 || (reccount !== 0 && !reccount) || typeof reccount != "number"){
			throw "SQL to get record count must return 1 record contain column 'reccount' as number.";
		}
		
		//全ページ数を計算
		var totalPages = Math.ceil(reccount / rows);
		
		//何ページ目を表示するか、の調整
		if(page > totalPages) page = totalPages;
		if(page < 1) page = 1;
		
		//実際に実行するSQL
		var execSql = "";
		
		//レコード選択用SQLに対してページングを設定
		if(noPager){
			//クライアント側でjqGridSelectDialogを使用しており、
			//autoRefresh処理のリクエストがあるなら、ページングは不要
			selSql.pager = "";
			execSql = selSql.sql;
			
		}else{
			if(st.enableOffset){
				//offset limit句が使用可能なDBの場合（PostgreSQL等）
				selSql.pager = " offset " + ((page - 1) * rows) + " limit " + rows;
				execSql = selSql.sql + selSql.pager;

			}else{
				//offset limit句が使用できないDBの場合（Oracle等）
				//pagerのSQLをoffset句のみにできないので、行番号を元にレコードを絞り込む
				//必要がある。こうなると、ページング部分のSQLだけ切り離すといったことが
				//できないので、SQL全体をpagerのSQLとして保存する。
				selSql.pager = ""
					+ "	select "
					+ "		bs.* "
					+ "	from "
					+ "		( " + selSql.sql + " ) bs "
					+ "	where "
					+ "		bs.row_n between " + ( (page - 1) * rows + 1 ) + " and " + ( page * rows )
					;
				execSql = selSql.pager;
			}
		}
		
		//レコード選択用SQLの実行
		nowSelect.selectSql = selSql.sql;
		nowSelect.selectParams = selSql.params;
		nowSelect.selectPagerSql = selSql.pager;
		params = this.makeDbParameters(selSql.params);
		var result = db.select( execSql, params );
		if (result.error) myLog.debug(result.sql, params);
		if (result.error) throw result;
		
		if(noPager){
			//クライアント側でjqGridSelectDialogを使用しており、
			//autoRefresh処理のリクエストがあるなら、データをそのまま返却
			return result.data;

		}else{
			/* ##################
			 * ### 通常の処理 ###
			 * ##################
			 */
			//最終的にクライアントに返却するレスポンスを編集（但し、現時点でデータは除く）
			$.extend(res, {
				page: page,
				total: totalPages,
				records: reccount
//				rows: result.data <---今回セレクト結果にデフォルトでは入れたくないのでここでは除外
			});

			//今回セレクト結果を更新
			nowSelect.response =  $.extend({}, res);
			
			//レコードをキャッシュするなら、今回セレクト結果にレコードを追加
			if(st.cacheRecord || request.cacheRecord == "true"){
				nowSelect.response.rows = $.extend(true, [], result.data);	//そのままつっこむとシリアライズできないと言われる...
			}
			
			//改めて、データをクライアントに返却するレスポンスに追加
			res.rows = result.data;
			
			//データ取得後の処理が指定されていたら実行
			if(typeof st.afterSelect == "function"){
				st.afterSelect.call(this, request, res, options);
			}
			
			//今回セレクト結果を「前回セレクト結果」として保存
			this.status.lastSelect = nowSelect;
			
			//レスポンスを返却
			return res;
		}
	},

	/* =============================================================================================
	 * 
	 * 関数名：	selectFromLast
	 * 内容：	前回のselectで取得したレコードの中から、追加の条件に該当するレコードを抽出して
	 * 			返却する。
	 * 			内部的には、前回のsqlを使用し、新たに指定された条件を付加した状態で再度sqlを発行
	 * 			する。ここでselectしたsql等の情報は当メソッドを抜けた時点で破棄され、セッション
	 * 			には保存されない。つまり、前回のselect結果は汚染されず、そのまま保持される。
	 * 
	 * 引数：	request	... HTTPリクエストパラメータ。
	 * 			
	 * 戻値：	取得したデータの配列
	 * 
	 * =============================================================================================
	 */
	selectFromLast: function(request){
		var st = this.settings;
		var sta = this.status;
		var last = $.extend({}, sta.lastSelect);
		
		if(!last) return;
		
		//検索条件の指定を強制的に付与
		request._search = "true";
		
		//検索条件の生成
		var sqlWhere = this.makeJqGridWhere(request, "last");
		
		//ソート順の生成
		var sqlOrderBy = this.makeJqGridOrderBy(request, "last");
		
		//SQLの生成
		var sql = ""
			+ "	select "
			+ "		last.* "
			+ "	from "
			+ "		( " + last.selectSql + " ) last "
			;
		if(sqlWhere){
			sql += ""
			+ "	where "
			+ "		" + sqlWhere
			;
		}
		if(sqlOrderBy){
			sql += ""
			+ "	order by "
			+ "		" + sqlOrderBy
			;
		}
		
		//SQLの実行
		var params = this.makeDbParameters(last.selectParams);
		var result = st.db.select(sql, params);
		if (result.error) myLog.debug(result.sql, params);
		if (result.error) throw result;
		
		//取得したデータの配列そのものをレスポンスとして返却
		return result.data;
	},
	
	/* =============================================================================================
	 * 
	 * 関数名：	edit
	 * 内容：	レコードの編集処理を行う。実装は onEdit 関数にまかせる。ここでは onEditを呼ぶのみ。
	 * 引数：	request	... HTTPリクエストパラメータ。
	 * 			options	... [onEdit]関数に引き渡す任意のオプションを任意の形式で指定する。
	 * 			
	 * 戻値：	クライアントに返却するレスポンスオブジェクト
	 * 
	 * =============================================================================================
	 */
	edit: function(request, options){
		var func = this.settings.onEdit;
		var res = null;
		if(typeof func == "function"){
			res = func.call(this, request, options);
		}
		return res;
	},

	/* =============================================================================================
	 * 
	 * 関数名：	getStatus
	 * 内容：	直近のリクエスト情報等のステータス一式を返却する。
	 * 引数：	
	 * 戻値：	ステータスオブジェクト
	 * 
	 * =============================================================================================
	 */
	getStatus: function(){
		return this.status;
	},

	/* =============================================================================================
	 * 
	 * 関数名：	setStatus
	 * 内容：	直近のリクエスト情報等のステータス一式を設定する。
	 * 引数：	ステータスオブジェクト
	 * 戻値：	
	 * 
	 * =============================================================================================
	 */
	setStatus: function(status){
		return this.status = status;
	},
	
	/* =============================================================================================
	 * 
	 * 関数名：	initStatus
	 * 内容：	直近のリクエスト情報等のステータス一式を初期化する。
	 * 引数：	
	 * 戻値：	
	 * 
	 * =============================================================================================
	 */
	initStatus: function(){
		return this.status = $.extend({}, arguments.callee.initStatus);
	},
	
	/* =============================================================================================
	 * 
	 * 関数名：	makeJqGridWhere
	 * 内容：	リクエストパラメータを元にjqGrid標準の検索条件を作成する。
	 * 引数：	
	 * 戻値：	Where句で指定する条件を表す文字列
	 * 
	 * =============================================================================================
	*/
	makeJqGridWhere: function(request, fieldPrefix) {
		fieldPrefix = fieldPrefix ? fieldPrefix + "." : "";
		
		var cr = "";
		var field;
		var oper;
		var str;
		
		//クライアント側でjqGridSelectDialogを使用しており、
		//suggestのリクエストがあるなら、条件を生成
		field	= request.suggestField;
		oper	= request.suggestOper;
		str		= request.suggestString;
		if(field && oper && (typeof str != "undefined")){
			cr = this.makeCriteria(fieldPrefix + field, oper, str);
		}

		//検索条件の指定が無ければ処理終了
		if(request._search != "true") return cr;
		
		if(request.filters){
			//jqGrid advanced searchの場合
			var filters = ImJson.parseJSON(request.filters);
			var groupOp = filters.groupOp || "and";
			for(var i=0,l=filters.rules.length; i<l; i++){
				var rule = filters.rules[i];
				cr += (cr? " " + groupOp + " " : "") + this.makeCriteria(fieldPrefix + rule.field, rule.op, rule.data);
			}
			
		}else{
			//jqGrid single searchの場合
			field	= request.searchField;
			oper	= request.searchOper;
			str		= request.searchString;
			
			if(field && oper && (typeof str != "undefined")){
				cr = this.makeCriteria(fieldPrefix + field, oper, str);
			}
		}
		
		//colModelを元に、各項目の値を取得して条件を生成
		var colModel = this.status.colModel;
		if(colModel && colModel instanceof Array){
			for(var i=0,l=colModel.length; i<l; i++){
				var col = colModel[i];
				var field = col.index || col.name;
				var data = request[field];
				if(data){
					cr += (cr? " and " : "") + this.makeCriteria(fieldPrefix + field, "eq", data);
				}
			}
		}
		
		return cr;
	},

	/* =============================================================================================
	 * 
	 * 関数名：	makeCriteria
	 * 内容：	条件文を作成する。
	 * 引数：	field	...	カラム名
	 * 			oper	...	検索方法を表すjqGridコード値
	 * 			value	...	検索対象の値
	 * 戻値：	条件文を表す文字列
	 * 
	 * =============================================================================================
	*/
	makeCriteria: function(field, oper, value) {
		//IN句か NOT IN句なら パラメータをスペース区切りで一旦ばらして
		//カンマ区切りに再編集する
		if( oper == "in" || oper == "ni" ){
			var valList = _HRS.parseCsv(value, " ");
			
			for(var i=0,l=valList.length; i<l; i++){
				//候補値の中にシングルクォーテーションが含まれていたら、
				//sql発行時のエスケープとして2倍に増やし、全体をシングル
				//クォーテーションで囲う
				valList[i] = literal(valList[i]);
			}
			value = valList.join(",");
		}
		
		//局所関数::シングルクォーテーションで囲む
		function literal(value){
			return "'" + value.replace(/'/g, "''") + "'";
		}
		
		switch(oper){
			case "eq":	//valueに等しい
				return field + " = " +  literal(value);
			
			case "ne":	//valueに等しくない
				return field + " != " +  literal(value);
			
			case "lt":	//次より小さい
				return field + " < " +  literal(value);
			
			case "le":	//次に等しいか小さい
				return field + " <= " +  literal(value);
			
			case "gt":	//次より大きい
				return field + " > " +  literal(value);
			
			case "ge":	//次に等しいか大きい
				return field + " >= " +  literal(value);
			
			case "bw":	//次で始まる
				value = this.wildCardLiteralEscapeByYenMark(value);
				return field + " like " +  literal(value + "%");
			
			case "bn":	//次で始まらない
				value = this.wildCardLiteralEscapeByYenMark(value);
				return field + " not like " +  literal(value + "%");
				
			case "cn":	//次を含む
				value = this.wildCardLiteralEscapeByYenMark(value);
				return field + " like " +  literal("%" + value + "%");
				
			case "nc":	//次を含まない
				value = this.wildCardLiteralEscapeByYenMark(value);
				return field + " not like " +  literal("%" + value + "%");
				
			case "ew":	//次で終る
				value = this.wildCardLiteralEscapeByYenMark(value);
				return field + " like " +  literal("%" + value);
			
			case "en":	//次で終らない
				value = this.wildCardLiteralEscapeByYenMark(value);
				return field + " not like " +  literal("%" + value);
			
			case "in":	//次に含まれる
				return field + " in(" + value + ")";
			
			case "ni":	//次に含まれない
				return field + " not in(" + value + ")";
		}
	},

	/* =============================================================================================
	 * 
	 * 関数名：	makeJqGridOrderBy
	 * 内容：	リクエストパラメータを元にjqGrid標準のソート条件を作成する。
	 * 引数：	
	 * 戻値：	Order By句で指定する条件を表す文字列
	 * 
	 * =============================================================================================
	*/
	makeJqGridOrderBy: function(request, fieldPrefix) {
		fieldPrefix = fieldPrefix ? fieldPrefix + "." : "";
		
		var sql = "";
		
		//jqGrid標準パラメータの取得
		var sidx = request.sidx;
		var sord = request.sord;
		
		//検索条件の指定が無ければ、空文字を返却
		if( !sidx ) return "";
		
		//複数項目の指定を想定して、カンマで分割する
		var sidxList = sidx.split(",");
		var sordList = sord.split(",");
		
		//全項目を精査し、該当するソート順があれば付加しつつ、SQL文字列を生成する
		for(var i=0,l=sidxList.length; i<l; i++){
			var col = sidxList[i].trim();
			if(!col) continue;

			//もしもソート方向が複数指定されていたなら、項目名と同期する。
			//ただ、標準では、multiSort指定の場合、ここには最後の項目のソート方向
			//のみが格納されているので、最後だけに付与しなければならない。
			var ord = (sordList.length > 1)? sordList[i] : "";
			
			sql += (sql ? "," : "") + col + (ord ? " " + ord : "");
		}
		
		//標準では、multiSort指定の場合、sordには最後の項目のソート方向
		//のみが格納されているので、最後だけに付加する。
		//また、通常の１項目のみのソートの場合も、上記のfor文の中では
		//ソート方向は編集されないので、ここで最後に付加する。
		//また、最後が " asc"、" desc"等になっていたら付加しない。
		//場合によってカラム名にソート方向が含まれていることがあるから、
		//その場合は付加してはいけない。
		if( sordList.length == 1 && !((/ (asc|desc)$/i).test(sql)) ) sql += " " + sordList[0];
		
		return " " + sql + " ";
	},

	/* =============================================================================================
	 * 
	 * 関数名：	makeDbParameters
	 * 内容：	バインド変数オブジェクト配列を元に、DbParameterの配列を生成する
	 * 引数：	バインド変数オブジェクトの配列
	 * 戻値：	DbParameterの配列
	 * 
	 * =============================================================================================
	 */
	makeDbParameters: function(params) {
		var dbPrms = new Array();
		
		if( !params || !(params instanceof Array) ) return dbPrms;
		
		for(var i=0,l=params.length; i<l; i++){
			var param = params[i];
			dbPrms.push(new DbParameter(param.data, param.type));
		}
		return dbPrms;
	},

	/* =============================================================================================
	 * 
	 * 関数名：	wildCardLiteralEscapeByYenMark
	 * 内容：	ワイルドカード文字をエスケープする
	 * 引数：	value	...	対象文字列
	 * 戻値：	エスケープ後の文字列
	 * 
	 * =============================================================================================
	 */
	wildCardLiteralEscapeByYenMark: function(value){
		var str = value.replace(/\\/g,"\\\\");	//"\"	→ \\
		str = str.replace(/_/g,"\\_");			// "_"	→ \_
		str = str.replace(/%/g,"\\%");			// "%"	→ \%
		return str;
	}
}

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * 
 * 
 * Privateメソッド
 * 
 * 
 */
/* =============================================================================================
 * 
 * 関数名：	getRequestParams
 * 内容：	リクエストオブジェクトからパラメータのみを抜き出して返却する。
 * 引数：	request	...	リクエストオブジェクト
 * 戻値：	パラメータを全て含むオブジェクト
 * 
 * =============================================================================================
 */
function getRequestParams(request){
	var params = {};
	for(var name in request){
		params[name] = request[name];
	}
	return params;
}

