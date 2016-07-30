<?php

require_once BASEDIR.'/server/utils/htmlclasses/HtmlBase.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar, inputvar

 class HtmlTreeCol
 {
     static private $IdCounter = 0;
     public $Width;
     public $Id;
     public $ColName;
     public $ValueName;
     public $Field;
     public $Title;
     public $Hidden;
     public $ReadOnly;
     public $Actions;
     public $CombinedCols;

     function __construct($width, $field, $colname, $valuename, $title, $readonly = false)
     {
         self::$IdCounter++;
         $this->Id = self::$IdCounter;
         $this->Width = $width;
         $this->Field = $field;
         $this->ColName = $colname;
         $this->ValueName = $valuename;
         $this->Title = $title;
         $this->Hidden = false;
         $this->ReadOnly = $readonly;
         $this->Actions = array();
         $this->CombinedCols = null;
     }
 }

 class HtmlTreeNode
 {
     static private $IdCounter = 0;
     public $Id;
     public $ParentNode;
     public $NodeId;
     public $Type;
     public $Name;
     public $Values;
     public $Level;
     public $Expanded;
     public $Children;
     public $Color;
     public $Hidden;
     public $Field;
     public $Action;
     public $ReadOnly;
     public $Tag;

     function __construct($parentnode, $type, $name, $values = null)
     {
         self::$IdCounter++;
         $this->Id = self::$IdCounter;
         $this->ParentNode = $parentnode;
         $this->Type = $type;
         $this->Name = $name;
         $this->Values = $values;
         $this->Expanded = false;
         $this->Hidden = false;
         $this->Color = '#DDDDDD';
         $this->Field = null;
         $this->Action = null;
         $this->ReadOnly = false;
         $this->Tag = null;
         if ($parentnode)
         {
			if ($parentnode->ParentNode == null)
			{
                $this->NodeId = 'p' . '0' . 'n' . $this->Id;
               $this->Level = 1;
			}
			else
			{
               $this->NodeId = 'p' . $parentnode->Id . 'n' . $this->Id;
               $this->Level = $parentnode->Level + 1;
			}
         }
         else
         {
             $this->NodeId = 'p' . '0' . 'n' . $this->Id;
             $this->Level = 0;
         }
         $this->Children = array();
     }
 }

 class HtmlTree extends HtmlBase
 {
     public $RootNode;
     public $CurNode;
     public $SelectedNode;
     public $Cols;
     public $DisplayLevel;
     public $IndentSize;
     public $ExpandCol;
     public $NameCol;
     public $TypeCol;
     public $UseDebugCol;
     public $ExpandGif;
     public $CollapseGif;
     public $LevelColors;

     function __construct($owner, $name)
     {
         HtmlBase::__construct($owner, $name);
         $this->RootNode = new HtmlTreeNode(null, 'root', 'root');
         $this->CurNode = $this->RootNode;
         $this->SelectedNode = null;
         $this->DisplayLevel = 2;
         $this->IndentSize = 5;
         $this->UseDebugCol = false;
         $this->ExpandGif = '../../config/images/expand.gif';
         $this->CollapseGif = '../../config/images/collapse.gif';
         $this->LevelColors = array('#bbbbbb', '#cccccc', '#dddddd', '#eeeeee', '#e8e8e8');
         $this->HeaderColor = '#aaaaaa';
     }

     function getName()
     {
         return $this->Name;
     }

     public function addCol($width, $field, $colname, $valuename, $title, $readonly = false)
     {
         $newcol = new HtmlTreeCol($width, $field, $colname, $valuename, $title, $readonly);
         $this->Cols[] = $newcol;
         return $newcol;
     }

     public function addCombinedCol($width, $colname, $title, $combinedcols)
     {
      $newcol = self::addCol($width, null, $colname, null, $title, false);
      $newcol->CombinedCols = $combinedcols;
      return $newcol;
     }

     public function addKeyCol($width, $title)
     {
         $newkeycol = self::addCol($width, null, '_keycol', '', $title, true);
         return $newkeycol;
     }

     public function addValueCol($width, $title, $readonly = false)
     {
         $newvaluecol = self::addCol($width, null, '_valuecol', '', $title, $readonly);
         return $newvaluecol;
     }

     public function addHintCol($width, $title)
     {
         $hintcol = self::addCol($width, null, '_hintcol', '', $title, true);
         return $hintcol;
     }


     public function addNameCol($width, $title)
     {
         $newnamecol = self::addCol($width, null, '_namecol', '', $title, true);
         return $newnamecol;
     }


     public function addExpandCol($width, $title)
     {
         $newexpandcol = self::addCol($width, null, '_expandcol', '', $title);
         return $newexpandcol;
     }

     public function addActionCol($width, $title, $action)
     {
         $actioncol = self::addCol($width, null, '_actioncol', '', $title);
         $actioncol->Actions[] = $action;
         return $actioncol;
     }

     public function addField($key, $field, $readonly, $hint = null, $err = null)
     {
         $values = array();
         $values['hint'] = $hint;
         $values['err'] = $err;
         $newnode = self::beginNode('', $key, $values);
         $newnode->Field = $field;
         $newnode->Color = '#cccccc';
         $newnode->ReadOnly = $readonly;
         self::endNode();
         return $newnode;
     }

     public function addActionRow($action)
     {
         $values = array();
         $values['hint'] = $action->Hint;
         $newnode = self::beginNode('Action', '' , $values);
         $newnode->Action = $action;
         $newnode->Color = '#cccccc';
         self::endNode();
         return $newnode;
     }

     public function beginNode($type, $name, $values = null, $tag = null)
     {
         $newnode = new HtmlTreeNode($this->CurNode, $type, $name, $values);
         $this->CurNode->Children[] = $newnode;
         $this->CurNode = $newnode;
         $this->CurNode->Tag = $tag;
         $this->CurNode->Color = $this->LevelColors[$newnode->Level];
         return $newnode;
     }

     public function endNode()
     {
         assert($this->CurNode);
         $result = $this->CurNode;
         $this->CurNode = $this->CurNode->ParentNode;
         return $result;
     }

     public function drawHeader()
     {
         $result  = "<SCRIPT language='Javascript' src='../utils/javascript/HtmlTree.js'></SCRIPT>";
         return $result;
     }

     public function drawBody()
     {
         assert($this->CurNode === $this->RootNode);

         $tablename = $this->Name;
         $result = '<table id="'.$tablename.'" width="800">';
         $result .= self::drawColHeaders();
         foreach ($this->RootNode->Children as $childnode)
         {
             $result .= self::drawNode($childnode);
         }
         $result .= '</table>';
         return $result;
     }

     public function drawColHeaders()
     {
         $result = '<tr bgcolor="'.$this->HeaderColor.'">';
         foreach ($this->Cols as $col)
         {
             if (!$col->Hidden)
             {
                 $width = $col->Width ? 'width="'.$col->Width.'"' : '';
                 $result .= "<td $width>";
                 $result .= $col->Title;
                 $result .= "</td>";
             }
         }
         $result .= "</tr>";
         return $result . "\n\n";
     }

     public function requestValues()
     {
         $result = array();
         foreach ($_POST as $key => $value)
         {
             $keyarray = explode('_', $key);
             if (count($keyarray) >= 3)
             {
                 $treename = formvar($keyarray[0]);
                 $tag = formvar($keyarray[1]);
                 $colname = formvar($keyarray[2]);

                 if ($treename == $this->Name)
                 {
                     foreach ($this->Cols as $col)
                     {
                         if ($col->Field && $col->ColName == $colname && $col->ReadOnly == false)
                         {
                             $col->Field->Name = $treename . '_' . $tag . '_' . $colname;
                             $value = $col->Field->requestValue();
                             $result[$tag][$col->ValueName] = formvar($value);
                             break;
                         }
                     }
                 }
             }
         }
         return $result;
     }

     public function drawCell($htmltreenode, $col, $incombinedcol = false)
     {

      $result = '';
      if (is_array($col->CombinedCols))
      {
         foreach($col->CombinedCols as $tempcol)
         {
            $result .= self::drawCell($htmltreenode, $tempcol, true);
            $result .= '&nbsp;';
         }
         return $result;
      }

      if (!$incombinedcol)
      {
             if ($col->Hidden)
	        {
            return '';
         }
			else if ($col->Field)
             {
                 if( $col->Field instanceof HtmlColorField )
                 {
					if (is_null($col->Field->getBoxSize()) && array_key_exists($col->ValueName, $htmltreenode->Values))
                     {
                         $bgcolor = 'bgcolor="'.$htmltreenode->Values[$col->ValueName].'"';
                         $result .= "<td $bgcolor>";
                     }
                     else
                     {
                         $result .= "<td>";
                     }
                 }
                 else
                 {
                     $result .= "<td>";
                 }
             }
             else
			{
				$result .= "<td>";
			}
      }

      switch ($col->ColName)
      {
         case '_valuecol':
         {
				if ($htmltreenode->Field)
				{
					if ($htmltreenode->ReadOnly)
					{
						$result .= formvar($htmltreenode->Field->getDisplayValue());
					}
					else
					{
						$result .= $htmltreenode->Field->drawBody();
					}
				}
				else if ($htmltreenode->Action)
				{
					$result .= $htmltreenode->Action->drawBody();
				}
				else
				{
					$result .= 'ERR';
				}
				break;
         }

         case '_hintcol':
         {
            $result .= formvar($htmltreenode->Values['hint']);
            break;
         }

         case '_actioncol':
         {
                 foreach ($col->Actions as $action)
                 {
                     $result .= $action->drawBodyWithTag($htmltreenode->Tag);
                 }
                 break;
         }

         case '_expandcol':
         {
                 $indent = '';
                 for ($i=0; $i<$htmltreenode->Level-1; $i++)
                 {
                  for ($j=0; $j<$this->IndentSize; $j++)
                  {
                     $indent .= '&nbsp;';
                  }
                 }
                 if (count($htmltreenode->Children))
                 {
		            $nodeid = $htmltreenode->NodeId . 'c' . count($htmltreenode->Children). 'z';
                  $expandgif = $this->ExpandGif;
                  $treename = formvar($this->Name);
                  $id = $htmltreenode->Id;
                  $buttonid = 'btn_' . $nodeid;
                  $onclick = "onClick=\"javascript:OnRowClicked('$treename','$id'); return false;\"";
                  $result .= '<a id="'.$buttonid.'" href="" '.$onclick.'>'.$indent.'<img src="'.$expandgif.'" border="0"></img></a>';
                 }
                 else
                 {
                  $result .= '';
                 }
                 break;
         }

         case '_namecol':
         {
                 $result .= formvar($htmltreenode->Name);
                 break;
         }

         case '_keycol':
         {
            $result .= formvar($htmltreenode->Name);
                 break;
         }

         default:
         {
				if ($col->Field)
				{
                     if (array_key_exists($col->ValueName, $htmltreenode->Values))
                     {
                         $col->Field->setValue($htmltreenode->Values[$col->ValueName]);
                     }
                     else
                     {
                         $col->Field->setValue(null);
                     }

                     if ($col->ReadOnly || $htmltreenode->ReadOnly)
                     {
                         $result .= formvar($col->Field->getDisplayValue());
                     }
                     else if ($htmltreenode->ReadOnly)
                     {
						$col->Field->ReadOnly = true;
                         $col->Field->Name = $this->Name . '_' . $htmltreenode->Tag . '_' . $col->ColName;
                        $result .= $col->Field->drawBody();
                     }
                     else
                     {
						$col->Field->ReadOnly = false;
                         $col->Field->Name = $this->Name . '_' . $htmltreenode->Tag . '_' . $col->ColName;
                         $result .= $col->Field->drawBody();
                     }
				}
				else
				{
					if (array_key_exists($col->ValueName, $htmltreenode->Values))
					{
						$result .= formvar($htmltreenode->Values[$col->ValueName]);
					}
					else
					{
						$result .= '&nbsp;';
					}
				}
            break;
         }
      }
      if (!$incombinedcol)
      {
         $result .= '</td>';
      }
      return $result;
     }

     public function drawNode($htmltreenode)
     {
         $result = '';
		$nodeid = $htmltreenode->NodeId . 'c' . count($htmltreenode->Children). 'z';
         $nodecolor = $htmltreenode->Color;
         $result .= "<tr id=$nodeid bgcolor=$nodecolor>";

         foreach ($this->Cols as $col)
         {
			$result .= self::drawCell($htmltreenode, $col);
         }

         $result .= '</tr>';
         foreach ($htmltreenode->Children as $childnode)
         {
             $result .= self::drawNode($childnode);
         }
		$result .= "\n\n";
         return $result;
     }

     public function drawNodeVersion2($htmltreenode)
     {
         $nodecolor = $htmltreenode->Color;
         $result = '';
         $nodeid = $htmltreenode->NodeId . 'c' . count($htmltreenode->Children). 'z';
         $result .= '<tr id="'.$nodeid.'" bgcolor="'.$nodecolor.'">';

         foreach ($this->Cols as $col)
         {
             if (is_null($col->Field))
             {
                 if ($col->ColName === '_valuecol')
                 {
                     $result .= "<td>";
                     if ($htmltreenode->Field)
                     {
                         if ($htmltreenode->ReadOnly)
                         {
                             $result .= formvar($htmltreenode->Field->getDisplayValue());
                         }
                         else
                         {
                             $result .= $htmltreenode->Field->drawBody();
                         }
                     }
                     else if ($htmltreenode->Action)
                     {
                         $result .= $htmltreenode->Action->drawBody();
                     }
                     $result .= "</td>";
                     continue;
                 }
                 else if ($col->ColName === '_hintcol')
                 {
                     $result .= "<td>";
                     $result .= formvar($htmltreenode->Values['hint']);
                     $result .= "</td>";
                     continue;
                 }
                 else if ($col->ColName === '_actioncol')
                 {
                     $result .= "<td>";
                     foreach ($col->Actions as $action)
                     {
                         $result .= $action->drawBodyWithTag($htmltreenode->Tag);
                     }
                     $result .= "</td>";
                     continue;
                 }
                 else if ($col->ColName === '_expandcol')
                 {
                     $indent = '';
                     for ($i=0; $i<$htmltreenode->Level-1; $i++)
                     {
                         for ($j=0; $j<$this->IndentSize; $j++)
                         {
                             $indent .= '&nbsp;';
                         }
                     }
                     if (count($htmltreenode->Children))
                     {
                         $expandgif = $this->ExpandGif;
                         $treename = $this->Name;
                         $id = $htmltreenode->Id;
                         $buttonid = 'btn_' . $nodeid;
                         $onclick = "onClick=\"javascript:OnRowClicked('$treename','$id'); return false;\"";
                         $result .= "<td><a id='$buttonid' href='' $onclick >$indent<img src='$expandgif' border=0></img></a></td>";
                     }
                     else
                     {
                         $result .= "<td></td>";
                     }
                     continue;
                 }
                 else if ($col->ColName === '_namecol' || $col->ColName === '_keycol')
                 {
                     $value = formvar($htmltreenode->Name);
                     $result .= "<td>$value</td>";
                     continue;
                 }
                 else if (array_key_exists($col->ValueName, $htmltreenode->Values))
                 {
					$value = formvar($htmltreenode->Values[$col->ValueName]);
                  $result .= "<td>$value</td>";
                     continue;
                 }
                 else
                 {
                  $result .= "<td>&nbsp;</td>";
                  continue;
                 }
             }
             else
             {
                 if( $col->Field instanceof HtmlColorField )
                 {
                     $color = $htmltreenode->Values[$col->ValueName];
                     $result .= "<td bgcolor=$color></td>";
                     continue;
                 }
                 else
                 {
                     $result .= "<td>";
                     if ($col->ColName === '_namecol')
                     {
                         $result .= $htmltreenode->Name;
                     }
                     else if (array_key_exists($col->ValueName, $htmltreenode->Values))
                     {
                         $col->Field->setValue($htmltreenode->Values[$col->ValueName]);
                     }
                     else
                     {
                         $col->Field->setValue(null);
                     }

                     if ($col->ReadOnly || $htmltreenode->ReadOnly)
                     {
                         $result .= formvar($col->Field->getDisplayValue());
                     }
                     else if ($htmltreenode->ReadOnly)
                     {
						$col->Field->ReadOnly = true;
                         $col->Field->Name = $this->Name . '_' . $htmltreenode->Tag . '_' . $col->ColName;
                        $result .= $col->Field->drawBody();
                     }
                     else
                     {
						$col->Field->ReadOnly = false;
                         $col->Field->Name = $this->Name . '_' . $htmltreenode->Tag . '_' . $col->ColName;
                         $result .= $col->Field->drawBody();
                     }
                     $result .= "</td>";
                     continue;
                 }
             }
         }
/*
         if ($this->UseDebugCol)
         {
             $debug = '';
             foreach ($htmltreenode->Values as $valueid => $value)
             {
                 if (!is_numeric($valueid))
                 {
                     $debug .= "$valueid:$value ";
                 }
             }
             $result .= "<td width=100>$debug</td>";
         }
*/            
         $result .= "</tr>\n";
         foreach ($htmltreenode->Children as $childnode)
         {
             $result .= self::drawNode($childnode);
         }
         return $result . "\n\n";
     }
 }