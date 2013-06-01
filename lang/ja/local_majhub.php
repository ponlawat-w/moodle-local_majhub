<?php // $Id: local_majhub.php 230 2013-03-01 08:48:24Z malu $

$string['pluginname'] = 'MAJ Community Hub';

$string['leaderboard'] = 'ランキング';
$string['searchcriteria'] = '検索条件';
$string['searchresults'] = '検索結果';

$string['mostdownloaded'] = 'ダウンロード数';
$string['mostreviewed'] = 'レビュー数';
$string['toprated'] = '高評価';
$string['latest'] = '新着';

$string['optionalfields'] = '拡張フィールド';
$string['keywords'] = 'キーワード';
$string['title'] = 'コース名';
$string['contributor'] = '投稿者';
$string['uploadedat'] = 'アップロード日';
$string['filesize'] = 'ファイルサイズ';
$string['version'] = 'バージョン';
$string['demourl'] = 'デモサイト';

$string['preview'] = 'プレビュー';
$string['download'] = 'ダウンロード';
$string['demosite'] = 'デモサイト';

$string['sortby:newest'] = '新着順';
$string['sortby:oldest'] = '登録順';
$string['sortby:title'] = 'コース名昇順';
$string['sortby:contributor'] = '投稿者名昇順';
$string['sortby:rating'] = '高評価順';

$string['coursewaresperpage'] = '１ページあたりの表示数';
$string['searchforcoursewares'] = 'コースウェアを検索';
$string['showoptionalcriteria'] = '拡張フィールドを表示';
$string['hideoptionalcriteria'] = '拡張フィールドを隠す';

$string['previewthiscourseware'] = 'このコースウェアをプレビュー';
$string['downloadthiscourseware'] = 'このコースウェアをダウンロード';
$string['visitauthorsdemosite'] = '作者のデモサイトを訪れる';
$string['editcoursewaremetadata'] = 'コースウェアのメタデータを編集する';
$string['previewcourseisnotready'] = 'プレビューコースの生成には10分以上かかることがあります。後ほどこのページを訪れてください。';

$string['noresult'] = '条件にマッチするコースウェアがありません';

$string['costspoints'] = '必要ポイント: {$a}';
$string['youhavepoints'] = 'あなたの保有ポイント: {$a}';
$string['howtogetpoints'] = 'ポイント獲得方法';
$string['howtogetpoints.desc'] = '<ul>
<li>コースをアップロードする<br />
    + {$a->pointsforuploading} pt/アップロード</li>
<li>レビューを書く<br />
    + {$a->pointsforreviewing} pt/レビュー</li>
<li>ボーナスをリクエスト<br />
    (= 管理者へメールを送る)</li>
</ul>';

$string['review'] = 'レビュー';
$string['rating'] = '評価';
$string['moderator'] = 'モデレーター';
$string['overallrating'] = '平均評価';
$string['latestreviews'] = 'レビュー {$a->total} 件中、最新 {$a->latest} 件';
$string['reviewinletters'] = 'レビューポイントを得るには {$a} 文字以上必要です';

$string['give'] = '進呈';

$string['settings/frontpage'] = 'フロントページ設定';
$string['settings/metafields'] = 'メタフィールド定義';
$string['settings/pointsystem'] = 'ポイントシステム設定';

$string['coursewaresperpageoptions'] = '１ページあたりのコースウェア表示数の選択肢';
$string['coursewaresperpagedefault'] = '１ページあたりのコースウェア表示数のデフォルト';

$string['pointacquisitions'] = '獲得ポイント';
$string['pointsforregistration'] = '新規登録ボーナスポイント';
$string['pointsforuploading'] = 'アップロードポイント';
$string['pointsforreviewing'] = 'レビューポイント';
$string['pointsforquality'] = '高品質ボーナスポイント';
$string['pointsforpopularity'] = '人気ボーナスポイント';
$string['countforpopularity'] = '人気ボーナスポイントを獲得するダウンロード数';
$string['lengthforreviewing'] = 'レビューコメントの最小文字数';

$string['pointconsumptions'] = '消費ポイント';
$string['pointsfordownloading'] = 'ダウンロードコスト';

$string['fieldtype'] = 'タイプ';
$string['fieldtype:text'] = 'テキスト';
$string['fieldtype:radio'] = 'ラジオボタン';
$string['fieldtype:check'] = 'チェックボックス';
$string['attributes'] = '属性';
$string['attributes:required'] = '必須';
$string['attributes:optional'] = '省略可';
$string['options'] = 'オプション';

$string['confirm:payfordownload'] = '{$a} ポイント使用してこのコースウェアをダウンロードしますか？';

$string['confirm:metafield:delete'] = '本当にこのメタフィールドを削除してもよろしいですか？';
$string['confirm:metafield:delete:warning'] = '警告！！
このメタフィールドに対して既にユーザーによって入力されたメタデータは完全に失います。
それらはもしあなたが同じメタフィールド名で再定義したとしても回復しません。';

$string['error:accessdenied'] = 'アクセスが拒否されました
(Hub サーバー上に複数のアカウントを持っている場合は、Hub Client で設定したアカウントでログインし直してください。)';
$string['error:missingcourseware'] = 'コースウェア #{$a} が見つかりません';
$string['error:youdonthaveenoughpoints'] = 'ポイントが不足しています';

$string['error:metafield:emptyname'] = '名称は必須です';
$stirng['error:metafield:emptyoptions'] = 'テキスト以外のタイプではオプションが必須です';
$string['error:metafield:duplicatename'] = 'この名称は既に使用されています';
$string['error:metafield:duplicateoption'] = 'オプションに重複した項目があります';
