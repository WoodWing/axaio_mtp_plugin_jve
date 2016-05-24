function debugconfirm(debugstring)
{
    confirm(debugstring);
}

function initTree(tableid, displaylevel)
{
	var table = findTable(tableid);
    var rows = table.rows;
	var i;

    for (i=1; i<rows.length; i++)
    {
        hideRow(rows[i]);
    }
    for (i=1; i<rows.length; i++)
    {
    	if (getParentRowNr(rows[i]) == '0')
    	{
    		showRow(rows[i]);
    		
    		if (displaylevel > 1)
			{
				// Change [+] into [-] button to indicate tree node is unfolded.
				var btn = document.getElementById("btn_" + rows[i].id);
				if( btn ) {
					btn.innerHTML = btn.innerHTML.replace('expand','collapse');
				}

				// Show all child nodes, underneath current tree node.
				var children = getChildRows(table, rows[i]);
				var j;
				for (j=0; j<children.length; j++)
				{
					curchild = children[j];
					showNode(table, curchild, displaylevel-1);
				}
			}
    	}
    }
}

function showNode(table, row, displaylevel)
{
	showRow(row);

	if (displaylevel > 1)
	{
		// Change [+] into [-] button to indicate tree node is unfolded.
		var btn = document.getElementById("btn_" + row.id);
		if( btn ) {
			btn.innerHTML = btn.innerHTML.replace('expand','collapse');
		}
		
		// Show all child nodes, underneath current tree node.
		var children = getChildRows(table, row);
		var i;
		for (i=0; i<children.length; i++)
		{
			curchild = children[i];
			showNode(table, curchild, displaylevel-1);
		}
	}
}

function findTable(tableid)
{
    //debugconfirm('findTable:' + tableid);
	var tables = document.getElementsByTagName('Table');	
	var i;
	for (i=0; i<tables.length; i++)
	{
		if (tables[i].id == tableid)
		{
			return tables[i];	
		}
	}
	return null;
}

function findRow(table, rownr)
{
    //debugconfirm('findRow:' + table.id + '&' + rownr);
    var rows = table.rows;
	var i;
    for (i=1; i<rows.length; i++)
    {
        findstr = 'n' + rownr + 'c';
        if (rows[i].id.indexOf(findstr) != -1)
        {
            return rows[i];   
        }
    }
    return null;
}

function Toggle(item, sz)
{
	var i;
	for (i=1;i<=sz;i++)
    {
		obj=document.getElementById(item+'-'+i);
		visible=(obj.style.display!="none")
		key=document.getElementById("x" + item);
		if (visible)
        {
			key.innerHTML="<img src='../../config/images/expand.gif' border=0>";
			obj.style.display="none";
		}
        else
        {
			key.innerHTML="<img src='../../config/images/collapse.gif' border=0>";
			try
            {
				obj.style.display="inline";
				obj.style.display="table-row";		// firefox
			}
            catch(error)
            {
			}
		}
	}
}

function OnRowClicked(tableid, rownr)
{
    //debugconfirm('onrowclicked:' + tableid + '&' + rownr);
    var table = findTable(tableid);
    var row = findRow(table, rownr);
    if (hasChildren(row))
    {
        var rows = table.rows;
        var i;
        for (i=1; i<rows.length; i++)
        {
            if (isChildRow(rows[i], row))
            {
                if (isRowVisible(rows[i]))
                {
                    collapseRow(table, row);   
                }
                else
                {
                    expandRow(table, row);   
                }
                return false;
            }
        }
    }
    return false;
}

function hideRow(row)
{
    //debugconfirm('hideRow:' + row.id);
    row.style.display = 'none';   
}

function showRow(row)
{
    //debugconfirm('showRow:' + row.id);
	try
    {
		row.style.display="inline";
		row.style.display="table-row";		// firefox
	}
    catch(error)
    {
	}
}

function isRowVisible(row)
{
//    debugconfirm('isRowVisible:' + row.id);
    if (row.style.display == 'none')
    {
        return false;
    }
    return true;
}

function isRowCollapsed(row)
{
    return false;    
}

function getRowNr(row)
{
    //debugconfirm('getRowNr:' + row.id);
    var npos = row.id.indexOf('n');
    var cpos = row.id.indexOf('c');
    var result = row.id.substr(npos+1, cpos-npos-1);
    return result;
}

function getParentRowNr(row)
{
    //debugconfirm('getParentRowNr:' + row.id);
    var ppos = row.id.indexOf('p');
    var npos = row.id.indexOf('n');
    var result = row.id.substr(ppos+1, npos-ppos-1);
    return result;
}

function getChildRows(table, parentrow)
{
    //debugconfirm('getChildRows:' + table.id + '&' + parentrow.id);
    var childarray = new Array();
    var rows = table.rows;
    var parentrownr = getRowNr(parentrow);
    for (i=0; i<rows.length; i++)
    {
        if (getParentRowNr(rows[i]) == parentrownr)
        {
            childarray.push(rows[i]);   
        }
    }
    return childarray;
}

function isChildRow(childrow, supposedparentrow)
{
//    debugconfirm('isChildRow:' + childrow.id + '&' + supposedparentrow.id);
    if (getRowNr(supposedparentrow) == getParentRowNr(childrow))
    {
        return true;   
    }
    return false;
}

function hasChildren(row)
{
//  debugconfirm('hasChildren:' + row.id');
    if (row.id.indexOf('c0z') == -1)
    {
        return true;   
    }
    return false;
}

function expandRow(table, row)
{
    //debugconfirm('expandRow:' + table.id + '&' + row.id);   
    if (!hasChildren(row))
    {
        return;
    }
    var rows = table.rows;
    var i;
    for (i=1; i<rows.length; i++)
    {
        var childrow = rows[i];   
        if (isChildRow(childrow, row) == true)
        {
            showRow(childrow);
        }
    }
	btn=document.getElementById("btn_" + row.id);
	var temp = btn.innerHTML;
    btn.innerHTML = temp.replace('expand','collapse');
}

function collapseRow(table, row)
{
    //debugconfirm('collapseRow:' + table.id + '&' + row.id);
    if (!isRowVisible(row))
    {
        return;   
    }
    
    var rows = table.rows;
    var i;
    for (i=1; i<rows.length; i++)
    {
        var childrow = rows[i];
        if (isChildRow(childrow, row) == true)
        {
            if (hasChildren(childrow))
            {
                collapseRow(table, childrow);
            }
            hideRow(childrow);
        }
    }
	btn=document.getElementById("btn_" + row.id);
	var temp = btn.innerHTML;
    btn.innerHTML = temp.replace('collapse','expand');
}
