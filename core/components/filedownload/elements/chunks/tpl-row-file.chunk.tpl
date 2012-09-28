
    <tr[[+fd.class]]>
        <td style="width:16px;"><img src="[[+fd.image]]" alt="[[+fd.image]]" /></td>
        <td><a href="[[+fd.url]]">[[+fd.filename]]</a>
            <span style="font-size:80%">([[+fd.count]] downloads)</span>
        </td>
        <td>[[+fd.sizeText]]</td>
        <td>[[+fd.date]]</td>
    </tr>
    [[-- This is the description row if the &chkDesc=`chunkName` is provided --]]
    [[+fd.description:notempty=`<tr>
        <td></td>
        <td colspan="3">[[+fd.description]]</td>
    </tr>`:default=``]]