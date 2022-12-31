<?php

return [

	'title' => 'Odds - レース予想アプリ',

	'admin' => '管理',
	'admin_add' => '追加',
	'admin_edit' => '編集',
	'admin_close' => '締切',
	'admin_reopen' => '再開',
	'admin_result' => '結果',
	'admin_save' => '保存',
	'admin_cancel' => 'キャンセル',
	'admin_confirm_close' => "レースの予想受付を締め切ります。\nよろしいですか？",
	'admin_confirm_reopen' => "レースの予想受付を再開します。\nよろしいですか？",
	'admin_confirm_finish' => "レースの結果を確定してよろしいですか？\\nこの操作はやり直しできません。",	// for confirm()
	'admin_error_input' => "入力に間違いがあります。",	// for confirm()

	'dialog_title_confirm' => '確認',
	'dialog_yes' => 'はい',
	'dialog_no' => 'いいえ',

	'game_future' => '今後のレース',
	'game_past' => '過去のレース',
	'game_id' => 'ID',
	'game_name' => 'レース名',
	'game_limit' => '予想期限',
	'game_limit_close' => '受付終了',
	'game_candidate' => '出走者',
	'game_bet' => 'このレースを予想する >>>',
	'game_bet_save' => '<<< 予想を保存',
	'game_list' => '<<< レース一覧',
	'game_info_closed' => '予想受付は終了しました',

	'candidate_order' => '番号',
	'candidate_name' => '出走者',
	'candidate_odds' => 'オッズ',
	'candidate_favorite' => '人気',
	'candidate_result' => '結果',
	'candidate_ranking' => '順位',

	'bet_points' => 'ポイント',
	'bet_win' => '単勝',
	'bet_quinella' => '馬連',
	'bet_exacta' => '馬単',
	'bet_place' => '複勝',
	'bet_tierce' => '３連単',
	'bet_trio' => '３連複',

	'user_tickers' => 'あなたの予想',
	'user_settings' => '設定',
	'user_reset' => '所持ポイントリセット',
	'user_reset_confirm' => 'すべての予想を破棄して所持ポイントをリセットします。\\nよろしいですか？',

	'info_top' => '* 重要 *<br>これはあくまでも「お遊び」です。課金要素は一切ありません。得られたポイントは次回のレースへ持ち越せるという以外に用途はありません。<br>ユーザ情報は Cookie を使用して保存しています。Cookie が消えれば所持ポイントはすべて消えます。その程度の「お遊び」であることをご理解の上、ご利用ください。',
	'info_odds' => '※オッズは５分毎に更新されます。',
	'info_points' => '※ポイントは受付が締め切られるまで何度でも再割り振り可能です。',
	'info_about' => 'このサイトのソースは <a href="https://github.com/ratty27/odds">GitHub</a> にて公開しています。',

	'internal_error' => '内部エラー',
];
