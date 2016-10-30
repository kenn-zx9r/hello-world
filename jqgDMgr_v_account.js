/* =============================================================================================
 * 
 * 
 * プログラム名		:	jqgDMgr_v_account.js
 * 内容				:	v_account(勘定科目マスタ)用のjqGridデータマネージャのインスタンスを生成
 * 						して返却する。
 * 
 * 
 * =============================================================================================
*/

//Procedureにバインドされている関数の参照時エイリアスを設定
var _HRS = Procedure.HRS;
var $ = Procedure.HRS.$;

//ログ出力クラス
var myLog	= new _HRS.MyLog("jqgDMgr_v_account.js");


/*
 * 関数名：init
 * 内容：メソッドを定義する。
 * 引数：なし
 * 戻値：なし
*/
function init()
{
	//名前空間HRSに、jqGridデータマネージャファクトリを登録する
	_HRS.jqgDMgr_v_account = _ME;
}


/*
 * 
 * 
 * ファクトリ定義
 * 
 * 
 */
var _ME = {
	/* =============================================================================================
	 * 
	 * 処理名		：create
	 * 摘要			：jqGridデータマネージャ生成
	 * 
	 * =============================================================================================
	 */
	create: function(){
		var options = {
			db: new SharedDatabase("common"),
			makeSelectSql: function(req){
				//検索基準日の設定
				var baseDate = req.baseDate;
				if( baseDate && _HRS.dateCheck(baseDate) ){
					baseDate = new Date(baseDate);
				}else{
					baseDate = new Date();
				}
				
				var sql = ""
					+ "	select "
					+ "		company_cd, "
					+ "		account_cd, "
					+ "		account_name, "
					+ "		dept_kbn, "
					+ "		require_kata_flg, "
					+ "		tax_free_flg, "
					+ "		note, "
					+ "		sort, "
					+ "		to_char(start_date_active, 'YYYY/MM/DD')	start_date_active, "
					+ "		to_char(end_date_active, 'YYYY/MM/DD')		end_date_active "
					+ "	from "
					+ "		v_account "
					+ "	where "
					+ "		company_cd = ? and "
					+ "		? between start_date_active and end_date_active "
					;
					
				//特定業務フラグの指定があれば、条件に加える
				if(req.useBizFlgName){
					sql += " and " + req.useBizFlgName + " = true ";
				}
				
				var params = new Array();
				params.push({
					data: req.company_cd,
					type: DbParameter.TYPE_STRING
				});
				params.push({
					data: baseDate,
					type: DbParameter.TYPE_DATE
				});
				
				return {
					sql: sql,
					params: params
				};
			}
		};
		
		var jqgDMgr = new _HRS.JqGridDataManager(options);
		return (jqgDMgr);
	}
}
