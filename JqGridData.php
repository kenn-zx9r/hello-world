<?php
/*
 * 
 * ファイル名：JqGridData.js
 * 内容：
 * 
 * 
*/

//クライアントのjqGridに返すJsonレスポンス
var responseJson;

//Procedureにバインドされている関数の参照時エイリアスを設定
var _HRS	= Procedure.HRS;
var $		= Procedure.HRS.$;

//ログ出力クラス
var myLog	= new _HRS.MyLog("JqGridData.js");
	
/* =============================================================================================
 * 
 * 関数名：init
 * 内容：初期表示メソッドを定義する。
 * 引数:@param req リクエストパラメータ.
 * 戻値：なし
 * 
 * =============================================================================================
 */
function init(req) {
	const MYNAME = "init";
	myLog.out(_HRS.LOG_I, MYNAME);

	var res;
	
	if(req.factoryPath == "conf"){
		//クライアントより指定されたjqGridのソースのパスを取得
		if(req.method == "getClientScriptPath"){
			res = {path: getJqGridPath(req.docpath, req.min)};
		}

	}else{
		//jqGridDataManagerよりレスポンスを取得
		var res = getJqGridResponse(req);
	}
	
	responseJson = ImJson.toJSONString(res, false);
}

/* =============================================================================================
 * 
 * 関数名：getJqGridPath
 * 内容：クライアントで開いているページのurlのパスより、該当のjqGridのスクリプトのパスを取得する
 * 引数:@param req リクエストパラメータ.
 * 戻値：jqGridのスクリプトのパス
 * 
 * =============================================================================================
 */
function getJqGridPath(docpath, min){
	var conf = _HRS.jqGridConf;
	
	//IM-Workflow の API によってページが取得された場合、エスケープされている
	//文字が有るので、元の文字に戻しておく。
	// "(2f)"	->	"/"
	// "(5f)"	->	"_"
	docpath = docpath.replace(/\(2f\)/g, "/");
	docpath = docpath.replace(/\(5f\)/g, "_");
	
	for(var i=0,li=conf.scriptPathList.length; i<li; i++){
		var pathConf = conf.scriptPathList[i];
		
		for(var j=0,lj=pathConf.targetUrls.length; j<lj; j++){
			var re = new RegExp(pathConf.targetUrls[j]);
			if(re.test(docpath)){
				if(min && pathConf.min){
					return pathConf.min;
				}else{
					return pathConf.src;
				}
			}
		}
	}

	//見つからなかった場合、空文字を返却
	return "";
}

/* =============================================================================================
 * 
 * 関数名：getJqGridResponse
 * 内容：該当のjqGridDataManagerよりレスポンスを得る。
 * 引数:@param req リクエストパラメータ.
 * 戻値：jqGridのレスポンスオブジェクト
 * 
 * =============================================================================================
 */
function getJqGridResponse(req){
	//jqgConnectIdがリクエストに含まれていなければエラー。
	//但し、クライアント側でjqGridSelectDialogを使用しており、
	//※autoRefresh処理のリクエストならば、セッション管理外として
	//  エラーとしない。
	if( !req.jqgConnectId && req.jqGridSelectDialogAutoRefresh != "true"){
		throw "'jqgConnectId' is not exist on request parameters.";
	}
	
	//該当するjqGridManagerのファクトリを取得して、jqGridManagerを生成
	var factoryPath = req.factoryPath.split(".");
	var factory = Procedure;
	for(var i=0,l=factoryPath.length; i<l; i++){
		factory = factory[ factoryPath[i] ];
	}
	var jqgDataMgr = factory.create(req);

	//該当するjqGridManagerのステータスをセッションから取得して、存在
	//していればjqGridManagerに前回のステータスをセットする。
	//※但し、クライアント側でjqGridSelectDialogを使用しており、
	//  autoRefresh処理のリクエストならば、セッション管理外。
	if( req.jqGridSelectDialogAutoRefresh != "true" ){
		var sta = Client.get(req.jqgConnectId);
		if(sta) jqgDataMgr.setStatus(sta);
	}

	//メソッド名を取得する
	var method = req.method;

	//レスポンスを取得
	var res = jqgDataMgr[method](req);

	//今回のjqGridManagerのステータスをセッションに保存する
	//※但し、クライアント側でjqGridSelectDialogを使用しており、
	//  autoRefresh処理のリクエストならば、セッション管理外。
	if( req.jqGridSelectDialogAutoRefresh != "true" ){

// TODO: test
/*
var sta = jqgDataMgr.getStatus();
delete sta.lastSelect;
Client.set(req.jqgConnectId, sta);
*/
		Client.set(req.jqgConnectId, jqgDataMgr.getStatus());
	}

	return res;
}
