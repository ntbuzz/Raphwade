//
$("#add-paragraph").click(function () {  // セクションブロックから呼び出される
    var obj = $('.tabmenu .tab li.selected');   // コンテンツエリアは選択されていない場合がある
    var para_obj = {    // フォームのデータを生成
        section_id: obj.attr("id"),
        section:    obj.text(),             // ショートテキスト
        disp_id:    0,
        title:      '',
        contents:   '',
    };
    $("#paragraph_dialog").floatWindow("${#.Para-Add}",para_obj, function (e) {
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("paragraph/add");
//        alert("段落追加\n"+url+"\n"+objDump(e));
        $.post(url, e, function (data) { //リクエストが成功した際に実行する関数
//            alert(data);
            location.href = data ;
        }).fail(function() {
            alert( "error:"+url );
        });
        return false;
    });
    return false;
});
//==============================================================================
//   セクションを追加するダイアログ表示＆書き込み処理
$(".add-section").click(function () {
    if (ChapterData.length == 0) {
        alert("${#.Chap-Alert}");
        return false;
    }
    var sec_obj = {
        chapter_id: ChapterData.id,
        chapter:    ChapterData.title,
        disp_id:    0,
        title:      '',
        short_title:'',
        contents:   '',
    };
    $("#section_dialog").floatWindow("${#.Sec-Add}", sec_obj, function (e) {
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("section/add");
        $.post(url, e,function (data) { //リクエストが成功した際に実行する関数
            location.href = data;
        }).fail(function () {
            alert("error:" + url);
        });
        return false;
    });
    return false;
});
// セクション編集メニュー
$("#edit-section").click(function () {
    var id = $('.tabmenu .tab li.selected').attr("id");
    // JSONメソッドを使ってレコードデータを入手する
    var url = location.origin + location.pathname.controller_path("section/json") + id;
    $.getJSON(url, function (sec_obj) {
        // フォームにパラメータをセットし、完了時の処理関数を登録する
        $("#section_dialog").floatWindow("", sec_obj, function (e) {
            e["TabSelect"] = $('.tabmenu .tab li.selected').index();
            var url = location.pathname.controller_path("section/update") + e["id"];
//            alert("セクション編集\n"+url+"\n"+objDump(e));
            $.post(url, e, function (data) { //リクエストが成功した際に実行する関数
//                alert(data);
                location.href = data ;
            }).fail(function() {
                alert( "error:"+url );
            });
            return false;
        });
    });
    return false;
});
// セクション削除メニュー
$("#delete-section").click(function () {
    var id = $('.tabmenu .tab li.selected').attr("id");
    // データを取得
    var url = location.origin + location.pathname.controller_path("section/json") + id;
    $.getJSON(url, function (sec_obj) {
        $.dialogBox("${#.Sec-Del}","["+sec_obj.title + '] ${#.Del-Confirm}' ,function (result) {
            if (result) { // OK のときに削除
                var url = location.pathname.controller_path("section/delete") + id;
                $.post(url,function (data) {  //リクエストが成功した際に実行する関数
                    location.href = data;
                }).fail(function () {  //リクエストが失敗
                    alert("error:" + url);
                });
            } else alert("${#.Canceled}");
        });
    });
    return false;
});
//******************************************************************************
//  ツールバーメニューのクリックアクション
//==============================================================================
//   パートレコード編集ダイアログ表示＆書き込み処理
$("#part_edit").click(function () {
    if (!("id" in PartData)) {
        alert("${#.Part-Alert}");
        return false;
    }
    $("#part_dialog").floatWindow("${#.Part-Edit}",PartData, function (e) {
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("part/update") + e["id"];
        $.post(url, e,function (data) { //リクエストが成功した際に実行する関数
            location.href = data;
        }).fail(function () {
            alert("error:" + url);
        });
        return false;
    });
    return false;
});
//==============================================================================
//   パートレコードを追加するダイアログ表示＆書き込み処理
$("#part_add").click(function () {
    var part_obj = {
        disp_id:    0,
        title:      '',
        contents:   '',
    };
    $("#part_dialog").floatWindow("${#.Part-Add}",part_obj, function (e) {
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("part/add");
        $.post(url, e,function (data) { //リクエストが成功した際に実行する関数
            location.href = data;
        }).fail(function () {
            alert("error:" + url);
        });
        return false;
    });
    return false;
});
//==============================================================================
//   パートレコードを削除
$("#part_del").click(function () {
    if (!("id" in PartData)) {
        alert("パートが選択されていません！");
        return false;
    }
    $.dialogBox("${#.Part-Del}","["+PartData.title + '] ${#.Del-Confirm}' ,function (result) {
        if (result) { // OK のときに削除
            var id = PartData.id;
            var url = location.pathname.controller_path("part/delete") + id;
            $.post(url, function (data) { //リクエストが成功した際に実行する関数
                location.href = data;
            }).fail(function () {
                alert("error:" + url);
            });
        }
    });
    return false;
});
//==============================================================================
//   セクションを追加するダイアログ表示＆書き込み処理
$("#chap_edit").click(function () {
    if (!("id" in ChapterData)) {
        alert("${#.Chap-Alert}");
        return false;
    }
    $("#chapter_dialog").floatWindow("${#.Chap-Edit}",ChapterData, function (e) {
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("chapter/update") + e["id"];
        $.post(url, e,function (data) { //リクエストが成功した際に実行する関数
            location.href = data;
        }).fail(function () {
            alert("error:" + url);
        });
        return false;
    });
    return false;
});
//==============================================================================
//   チャプターレコードを追加するダイアログ表示＆書き込み処理
$("#chap_add").click(function () {
    if (!("id" in PartData)) {
        alert("${#.Part-Alert}");
        return false;
    }
    var chap_obj = {
        part_id:    PartData.id,
        disp_id:    0,
        title:      '',
        contents:   '',
    };
    $("#chapter_dialog").floatWindow("${#.Chap-Add}",chap_obj, function (e) {
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("chapter/add");
        $.post(url, e,function (data) { //リクエストが成功した際に実行する関数
            location.href = data;
        }).fail(function () {
            alert("error:" + url);
        });
        return false;
    });
    return false;
});
//==============================================================================
//   チャプターレコードを削除
$("#chap_del").click(function () {
    if (!("id" in ChapterData)) {
        alert("${#.Chap-Alert}");
        return false;
    }
    if(confirm(ChapterData.title+' ${#.Del-Confirm}')){
        /*　OKの時の処理 */
        var id = ChapterData.id;
        var url = location.pathname.controller_path("chapter/delete")+id;
        $.post(url, function (data) { //リクエストが成功した際に実行する関数
            alert(data);
            location.href = data;
        }).fail(function () {
            alert("error:" + url);
        });
    }
    return false;
});
//==============================================================================
//   テキストをクリップボードへコピー
$("#ctxCopy").click(function () {
//    alert('COPY');
    document.execCommand('copy');
    return false;
});
//==============================================================================
//   テキスト形式に変換
$('#text_downld').click(function () {
    var url = location.pathname.controller_path("index/download");
    location.href = url;
/*
    $.post(url, function (data) { //リクエストが成功した際に実行する関数
        alert(data);
    }).fail(function () {
        alert("error:" + url);
    });
*/
    return false;
});
