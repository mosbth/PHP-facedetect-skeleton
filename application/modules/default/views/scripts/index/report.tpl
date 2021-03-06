<div class="pr" style="height: 400px">
{foreach $histogram as $key=>$value}
<div class="fl"></div>
    <div class="pa" style="left:{$value@iteration*50}px; bottom: 0; width:25px;height: {$value}px;border: 1px solid {cycle values="#aaaaaa,#bbbbbb"}">
        {$key}px = {$value}
    </div>
{/foreach}
</div>

<div class="clear"></div>
<br>
{foreach $files as $file}

    <table style="background: #fff">
        <tr>
            <th colspan="20" style="text-align: left">
                {$file.file}<br>
                <img src="/images/random/{$file.file}" width="200" />
            </th>
        </tr>
    {foreach $file.rows as $row}
        <tr>
            {foreach $row as $cell}
                <td>
                    {if $cell|@is_array}
                        {$cell|@count}
                    {else}
                        {$cell}
                    {/if}
                </td>
            {/foreach}
        </tr>

    {/foreach}
    </table>
{/foreach}