/**
 *  MAJ Hub frontpage
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: frontpage.js 158 2012-12-03 05:49:58Z malu $
 */
M.local_majhub = M.local_majhub || {}
M.local_majhub.frontpage = new function ()
{
    /**
     *  Initialize
     *  
     *  @param {YUI} Y
     */
    this.init = function (Y)
    {
        var $mform = Y.Node.one('.mform a[name="searchcriteria"]').ancestor();

        var $firstoptional = $mform.one('.optional');
        if ($firstoptional) {
            var str = {
                'show': M.str.local_majhub['showoptionalcriteria'],
                'hide': M.str.local_majhub['hideoptionalcriteria']
            };
            var $wrapper = Y.Node.create('<div style="text-align:right; margin-bottom:-1.5em;"/>');
            var $togglebutton = Y.Node.create('<input type="button"/>').set('value', str['show']);
            var $optionalfields = $mform.all('.optional');
            function showoptionalcriteria()
            {
                $optionalfields.show();
                $togglebutton.set('value', str['hide']);
            }
            function hideoptionalcriteria()
            {
                $optionalfields.hide();
                $togglebutton.set('value', str['show']);
            }
            $togglebutton.on('click', function ()
            {
                if (this.get('value') == str['show']) {
                    showoptionalcriteria();
                } else {
                    hideoptionalcriteria();
                }
            });
            $firstoptional.ancestor().insertBefore($wrapper.append($togglebutton), $firstoptional);
            var nonempty = $optionalfields.some(function ($field)
            {
                var $input = $field.one('input[type=text],input:checked');
                return $input && $input.get('value').length != 0;
            });
            if (nonempty) {
                showoptionalcriteria();
            } else {
                hideoptionalcriteria();
            }
        }
    }
}
