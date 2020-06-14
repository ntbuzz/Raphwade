<?php

class IndexController extends AppController {
	public $defaultAction = 'Redirect';		//  デフォルトのアクション
	public $disableAction = [ 'List','Page','Find','Makepdf','Update','Dump' ];	// 無視するアクション

//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//==============================================================================
//	クラス初期化処理
	protected function ClassInit() {
//		MySession::PostToEnv(['TabSelect']);
//		$this->setEvent('Model.OnGetRecord',$this->View->Helper,"echo_dump");
	}
//==============================================================================
// デフォルトの動作
public function DisplayAction() {
	APPDEBUG::MSG(15,":Test");
	$this->View->PutLayout();
}
//==============================================================================
// デフォルトの動作
public function RedirectAction() {
	$url = App::Get_AppRoot();
	header("Location:{$url}");
}
//==============================================================================
// コンテンツビュー
public function ViewAction() {
	$Part = App::$Params[0];	// Doc-Part
	$Chap = App::$Params[1];	// Doc-Chapter
	$Tabs = MySession::$PostEnv['TabSelect'];	// POSTまたはENV変数から
	unset(MySession::$EnvData['TabSelect']);	// ENV変数は消去
	// URLに指定があればそちらを優先
	if(!empty(App::$Params[2])) $Tabs = App::$Params[2];
	if(empty($Tabs)) $Tabs = 0;
	// ツリーメニューを構築
	$this->Model->MakeOutline();
	// Section データを取得
	$this->Part->getRecordByKey($Part);
	$this->Chapter->getRecordByKey($Chap);
	$this->ViewSet(['PartData' => $this->Part->fields,'ChapterData' => $this->Chapter->fields]);

	$this->Section->getSectionDoc($Chap);
	$this->ViewSet(['Part' => $Part,'Chapter' => $Chap,'Section' => $this->Section->Records, 'Tabmenu' => $Tabs]);
	APPDEBUG::DebugDump(5, [
		'パラメータ' => App::$Params,
		'レコード' => $this->Model->Records,
		'アウトライン' => $this->Model->outline,
		'セクション' => $this->Section->Records,
		'タブ' => $Tabs,
	]);
	$this->View->PutLayout();
}
//==============================================================================
// アウトラインテキストのダウンロード
public function DownloadAction() {
	$outline = $this->Model->ReadOutline();		// レコードデータ
	$contents = $this->View->Helper->makeOutlineText($outline,0);
    debug_dump(0,[
//		'アウトライン' => $outline,
		'テキスト' => $contents,
	]);
	$filename = 'Readme.txt';			// リネーム後のファイル名
	header('Content-Type: text/plain');		// ファイルタイプにTEXTを指定
	header('Content-Length: '.strlen($contents));	// ファイルサイズを取得し、ダウンロードの進捗を表示
	// ファイルのダウンロード、リネームを指示
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	echo $contents;
}

}
