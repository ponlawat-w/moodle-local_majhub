<?php // $Id: metafields.php 176 2013-01-24 12:11:41Z malu $

require_once __DIR__.'/../../../config.php';
require_once __DIR__.'/../../../lib/adminlib.php';

require_once __DIR__.'/../classes/metafield.php';
require_once __DIR__.'/../classes/element.php';

use majhub\metafield;

function tag($tagName) { return new majhub\element($tagName); }

if (false) {
    $DB     = new mysqli_native_moodle_database;
    $OUTPUT = new core_renderer;
    $PAGE   = new moodle_page;
}

$editid   = optional_param('edit', 0, PARAM_INT);
$deleteid = optional_param('delete', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/majhub/admin/metafields.php'));
$PAGE->navbar->ignore_active(true);
$PAGE->navbar->add(get_string('administrationsite'));
$PAGE->navbar->add(get_string('pluginname', 'local_majhub'));
$PAGE->navbar->add(get_string('settings/metafields', 'local_majhub'), $PAGE->url);

admin_externalpage_setup('majhub/metafields');

if (optional_param('order', null, PARAM_TEXT)) {
    $weights = required_param_array('weights', PARAM_INT);
    foreach ($weights as $id => $weight) {
        $DB->set_field(metafield::TABLE, 'weight', $weight, array('id' => $id));
    }
    metafield::reorder();
    redirect($PAGE->url);
}
if (optional_param('save', null, PARAM_TEXT) && confirm_sesskey()) {
    $field = (object)required_param_array('field', PARAM_RAW);
    metafield::from_record($field)->save();
    metafield::reorder();
    redirect($PAGE->url);
}
if (optional_param('suredelete', null, PARAM_TEXT) && confirm_sesskey()) {
    metafield::from_id($deleteid, MUST_EXIST)->delete();
    metafield::reorder();
    redirect($PAGE->url);
}

$PAGE->requires->css('/local/majhub/admin/metafields.css');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('settings/metafields', 'local_majhub'));
echo $form = tag('form')->action($PAGE->url)->method('post')->start();

$addicon    = $OUTPUT->pix_icon('t/add', get_string('add'));
$editicon   = $OUTPUT->pix_icon('t/edit', get_string('edit'));
$deleteicon = $OUTPUT->pix_icon('t/delete', get_string('delete'));

$sesskeyhidden = tag('input')->type('hidden')->name('sesskey')->value(sesskey());
$cancelbutton  = tag('input')->type('button')->onclick("location.href = '{$PAGE->url}';")->value(get_string('cancel'));

$maxweight = 0;

echo $table = tag('table')->classes('metafields')->start();
echo tag('tr')->append(
    tag('th')->classes('order')->append(get_string('order')),
    tag('th')->classes('name')->append(get_string('name')),
    tag('th')->classes('format')->append(get_string('format')),
    tag('th')->classes('actions')->append(get_string('actions'))
    );
foreach (metafield::all() as $id => $field) {
    $maxweight = max($maxweight, $field->weight);
    $tr = tag('tr');
    if ($id == $editid)
        $tr->classes('editing');
    elseif ($field->required)
        $tr->classes('required');
    elseif ($field->optional)
        $tr->classes('optional');
    echo $tr->start();
    if ($id == $editid) {
        echo tag('td')->classes('order')->append(
            tag('input')->type('hidden')->name('field[weight]')->value($field->weight), $field->weight
            );
        echo tag('td')->append(tag('input')->type('text')->name('field[name]')->size(10)->value($field->name));
        echo tag('td')->append(
            tag('fieldset')->append(
                tag('legend')->append(get_string('attributes', 'local_majhub')),
                tag('label')->append(
                    tag('input')->type('checkbox')->name('field[required]')->checked($field->required),
                    get_string('attributes:required', 'local_majhub')),
                tag('label')->append(
                    tag('input')->type('checkbox')->name('field[optional]')->checked($field->optional),
                    get_string('attributes:optional', 'local_majhub'))
                ),
            tag('fieldset')->append(
                tag('legend')->append(get_string('fieldtype', 'local_majhub')),
                tag('label')->append(
                    tag('input')->type('radio')->name('field[type]')->value(metafield::TYPE_TEXT)
                        ->checked($field->type == metafield::TYPE_TEXT),
                    get_string('fieldtype:text', 'local_majhub')
                    ),
                tag('label')->append(
                    tag('input')->type('radio')->name('field[type]')->value(metafield::TYPE_RADIO)
                        ->checked($field->type == metafield::TYPE_RADIO),
                    get_string('fieldtype:radio', 'local_majhub')
                    ),
                tag('label')->append(
                    tag('input')->type('radio')->name('field[type]')->value(metafield::TYPE_CHECK)
                        ->checked($field->type == metafield::TYPE_CHECK),
                    get_string('fieldtype:check', 'local_majhub')
                    )
                ),
             tag('fieldset')->append(
                tag('legend')->append(get_string('options', 'local_majhub')),
                tag('textarea')->name('field[options]')->cols(60)->rows(8)->append(implode("\n", $field->options))
                )
           );
        echo tag('td')->append(
            tag('input')->type('hidden')->name('field[id]')->value($id), $sesskeyhidden,
            tag('input')->type('submit')->name('save')->value(get_string('update')), ' ', $cancelbutton
            );
    } else {
        if ($editid == 0) {
            echo tag('td')->classes('order')->append(
                tag('input')->type('text')->name("weights[$id]")->size(1)->value($field->weight)
                );
        } else {
            echo tag('td')->classes('order')->append($field->weight);
        }
        echo tag('td')->append($field->name);
        if ($id == $deleteid) {
            $confirmation = tag('div')->classes('confirm')->append(
                get_string('confirm:metafield:delete', 'local_majhub')
                );
            if ($DB->record_exists('majhub_courseware_metadata', array('metafieldid' => $id))) {
                $confirmation .= tag('div')->classes('warning')->append(
                    get_string('confirm:metafield:delete:warning', 'local_majhub')
                    );
            }
            echo tag('td')->append($field->render(), $confirmation);
            echo tag('td')->append(
                tag('input')->type('hidden')->name('delete')->value($id), $sesskeyhidden,
                tag('input')->type('submit')->name('suredelete')->value(get_string('delete')), ' ', $cancelbutton
                );
        } else {
            echo tag('td')->append($field->render());
            echo tag('td')->append(
                tag('a')->href("{$PAGE->url}?edit={$id}")->append($editicon), ' ',
                tag('a')->href("{$PAGE->url}?delete={$id}")->append($deleteicon)
                );
        }
    }
    echo $tr->end();
}
if ($editid < 0) {
    echo $tr = tag('tr')->classes('editing')->start();
    echo tag('td')->classes('order')->append(
        tag('input')->type('text')->name('field[weight]')->size(1)->value($maxweight + 1)
        );
    echo tag('td')->append(tag('input')->type('text')->name('field[name]')->size(10));
    echo tag('td')->append(
        tag('fieldset')->append(
            tag('legend')->append(get_string('attributes', 'local_majhub')),
            tag('label')->append(
                tag('input')->type('checkbox')->name('field[required]'),
                get_string('attributes:required', 'local_majhub')),
            tag('label')->append(
                tag('input')->type('checkbox')->name('field[optional]'),
                get_string('attributes:optional', 'local_majhub'))
            ),
        tag('fieldset')->append(
            tag('legend')->append(get_string('fieldtype', 'local_majhub')),
            tag('label')->append(
                tag('input')->type('radio')->name('field[type]')->value(metafield::TYPE_TEXT)->checked(true),
                get_string('fieldtype:text', 'local_majhub')
                ),
            tag('label')->append(
                tag('input')->type('radio')->name('field[type]')->value(metafield::TYPE_RADIO),
                get_string('fieldtype:radio', 'local_majhub')
                ),
            tag('label')->append(
                tag('input')->type('radio')->name('field[type]')->value(metafield::TYPE_CHECK),
                get_string('fieldtype:check', 'local_majhub')
                )
            ),
        tag('fieldset')->append(
            tag('legend')->append(get_string('options', 'local_majhub')),
            tag('textarea')->name('field[options]')->cols(60)->rows(8)->append('')
            )
        );
    echo tag('td')->append(
        tag('input')->type('hidden')->name('field[id]')->value(0), $sesskeyhidden,
        tag('input')->type('submit')->name('save')->value(get_string('add')), ' ', $cancelbutton
        );
    echo $tr->end();
} else {
    echo $tr = tag('tr')->start();
    if ($editid == 0) {
        echo tag('td')->classes('order')->append(
            tag('input')->type('submit')->name('order')->value(get_string('update'))
            );
    } else {
        echo tag('td')->append('');
    }
    echo tag('td')->colspan(2)->append('');
    echo tag('td')->append(tag('a')->href("{$PAGE->url}?edit=-1")->append($addicon));
    echo $tr->end();
}
echo $table->end();

echo $form->end();
echo $OUTPUT->footer();
