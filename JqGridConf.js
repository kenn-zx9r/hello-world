/*
 * 
 * ファイル名：JqGridConf.js
 * 摘要：jqGrid環境設定
 * 内容：jqGridに関する各種設定を保持する
 * 
 * 
 */


//Procedureにバインドされている関数の参照時エイリアスを設定
var _HRS = Procedure.HRS;
var $ = Procedure.HRS.$;

/* =============================================================================================
 * 関数名：init
 * 内容：メソッドを定義する。
 * 引数：なし
 * 戻値：なし
 * =============================================================================================
 */
function init(){

	//名前空間HRSに、jqGridに関する環境設定オブジェクトを登録する
	_HRS.jqGridConf = {
		scriptPathList: [
			{
				src: "js/lib/jquery.jqGrid-4.6.0/js/jquery.jqGrid.src.js",
				min: "js/lib/jquery.jqGrid-4.6.0/js/jquery.jqGrid.min.js",
				targetUrls: [
					".*/hrs/rattlepop/IV/.*",
					".*/hrs/rattlepop/MT/.*",
					".*/hrs/expense/biz_trip/screen/.*",
					".*/hrs/expense/entertainment/.*",
					".*/hrs/expense/gene_exp/.*",
					".*/hrs/expense/regular_reg/screen/.*",
					".*/hrs/expense/regular_pay/screen/.*",
					".*/hrs/expense/regular_mst/.*",
					".*/hrs/rounddoc/improve_report/screen/.*",
					".*/hrs/expense/trans_exp/screen/trans_history_pop"
				]
			}
		]
	};
}
