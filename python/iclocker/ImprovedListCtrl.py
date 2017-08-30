import wx

import wx.lib.newevent
import wx.lib.scrolledpanel 
from wx.lib.masked import NumCtrl
from wx.lib.intctrl import IntCtrl

from TestElements import *


PREVIOUS_ITEM = 0
NEXT_ITEM = 1

ListItemSelected, EVT_LIST_ITEM_SELECTED = wx.lib.newevent.NewEvent()
ListItemRightClick, EVT_LIST_ITEM_RIGHT_CLICK = wx.lib.newevent.NewEvent()


class Row(wx.Panel):
    def __init__(self, parent, id, data, width = [], backgroundColor = '#FFFFFF', selectColor = '#EEEEEE', hoverColor = '#FAFAFA', fontColor = '#000000', font = None):
        wx.Panel.__init__(self, parent)
        self.parent = parent
        self.id = id
        self.data = data
        self.dataType = []
        self.backgroundColor = backgroundColor
        self.hoverColor = hoverColor
        self.selectColor = selectColor
        self.fontColor = fontColor
        self.font = font
        self.enters = 0
        self.selected = False

        if width == []:
            width = [1] * len(data)
        if font == None:
            self.font = wx.Font(8, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_NORMAL)

        self.rowElements = []
        self.rowSizer = wx.BoxSizer(wx.HORIZONTAL)

        self.SetBackgroundColour(self.backgroundColor)

        for column in range(len(data)):
            self.dataType.append(type(data[column]))
            if self.dataType[column] == str or self.dataType[column] == unicode:
                self.rowElements.append(wx.TextCtrl(self, size = (10,-1), value = data[column], style = wx.TE_READONLY | wx.BORDER_NONE))
            elif self.dataType[column] == int:
                self.rowElements.append(IntCtrl(self, size = (10,-1), value = data[column], style = wx.TE_READONLY | wx.BORDER_NONE))
            self.rowElements[column].SetBackgroundColour(self.backgroundColor)
            self.rowElements[column].SetForegroundColour(self.fontColor)
            self.rowElements[column].SetFont(self.font)

            self.rowElements[column].Bind(wx.EVT_ENTER_WINDOW, self.OnRowEnter)
            self.rowElements[column].Bind(wx.EVT_LEAVE_WINDOW, self.OnRowLeave)
            self.rowElements[column].Bind(wx.EVT_SET_FOCUS, self.OnSelect)
            self.rowElements[column].Bind(wx.EVT_KILL_FOCUS, self.OnDeSelect)
            self.rowElements[column].Bind(wx.EVT_RIGHT_UP, self.OnRightClick)
            self.rowElements[column].Bind(wx.EVT_KEY_DOWN, self.OnListElementKeyDown)
            

            self.rowSizer.Add(self.rowElements[column], width[column], wx.EXPAND | wx.LEFT | wx.RIGHT, border = 5)


        self.SetSizerAndFit(self.rowSizer)
        self.Layout()

    def OnListElementKeyDown(self, event):
        #propagate event to main parent
        Level = event.StopPropagation()
        event.ResumePropagation(Level+2)
        event.Skip()

    def UpdateData(self, data):
        if len(data) != len (self.rowElements): return False

        for column in range(len(self.rowElements)):
            if self.dataType[column] == str or self.dataType[column] == unicode:
                self.rowElements[column].SetLabel(data[column])
            elif self.dataType[column] == int:
                self.rowElements[column].SetValue(data[column])
            self.rowElements[column].SetForegroundColour(self.fontColor)
            self.rowElements[column].SetFont(self.font)

    def SetWidth(self, width):
        self.rowSizer.Clear()
        for i in range(len(self.rowElements)):
            self.rowSizer.Add(self.rowElements[i], self.width[i], wx.EXPAND)
        
        self.Layout()

    def SetBackgroundColor(self, color):
        self.backgroundColor = color
        self.SetBackgroundColour(self.backgroundColor)
        self.Refresh()
        for column in range(len(self.rowElements)):
            self.rowElements[column].SetBackgroundColour(self.backgroundColor)
            self.rowElements[column].Refresh()

    def SetFontColor(self, color):
        self.fontColor = color
        for column in range(len(self.rowElements)):
            self.rowElements[column].SetForegroundColour(self.fontColor)
            self.rowElements[column].Refresh()

    def SetFont(self, font):
        self.font = font
        for column in range(len(self.rowElements)):
            self.rowElements[column].SetFont(self.font)
            self.rowElements[column].Refresh()
    
    def SetSelectColor(self, color):
        self.selectColor = color

    def SetHoverColor(self, color):
        self.hoverColor = color

    def OnSelect(self, event):
        wx.PostEvent(self.parent , ListItemSelected(id=self.id, data=self.data))
        self.parent.selectedItem = self.id
        self.selected = True

        self.SetBackgroundColour(self.selectColor)
        self.Refresh()
        for column in range(len(self.rowElements)):
            self.rowElements[column].SetBackgroundColour(self.selectColor)
            self.rowElements[column].Refresh()
    
    def OnDeSelect(self, event):
        self.selected = False

        self.SetBackgroundColour(self.backgroundColor)
        self.Refresh()
        for column in range(len(self.rowElements)):
            self.rowElements[column].SetBackgroundColour(self.backgroundColor)
            self.rowElements[column].Refresh()

    def OnRightClick(self, event):
        wx.PostEvent(self.parent , ListItemRightClick(id=self.id, data=self.data))

    def OnRowEnter(self, event):
        self.enters += 1
        if self.enters == 1 and not self.selected:
            self.SetBackgroundColour(self.hoverColor)
            self.Refresh()
            for column in range(len(self.rowElements)):
                self.rowElements[column].SetBackgroundColour(self.hoverColor)
                self.rowElements[column].Refresh()

    def OnRowLeave(self, event):
        self.enters -= 1
        if self.enters <= 0 and not self.selected:
            self.SetBackgroundColour(self.backgroundColor) 
            self.Refresh()
            for column in range(len(self.rowElements)):
                self.rowElements[column].SetBackgroundColour(self.backgroundColor)
                self.rowElements[column].Refresh()



class ImprovedListCtrl(wx.lib.scrolledpanel.ScrolledPanel):
    def __init__(self, parent):
        #wx.Panel.__init__(self, parent)
        wx.lib.scrolledpanel.ScrolledPanel.__init__(self, parent, style = wx.SIMPLE_BORDER)
        self.SetBackgroundColour('#FFFFFF')
        self.parent = parent

        self.order = True
        self.columnOrder = 0
        self.rowColors = ['#FFFFFF']
        self.rowSelectColor = '#EEEEEE'
        self.rowHoverColor = '#FAFAFA'
        self.rowFontColor = '#000000'
        self.rowFont = wx.Font(8, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_NORMAL)
        self.headerColor = '#DDDDDD'
        self.headerHoverColor = '#AAAAAA'
        self.headerFontColor = '#000000'
        self.headerFont = wx.Font(8, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD)
        self.backgroundColorCount = 0

        self.itemNum = 0
        self.items = {}
        self.selectedItem = -1

        self.filteredColumn = []

        self.columnNum = 0
        self.columnText = []
        self.columnHeader = []
        self.columnWidths = []

        self.Bind(wx.EVT_KEY_DOWN, self.OnListElementKeyDown)

        self.mainSizer = wx.BoxSizer(wx.VERTICAL)
        self.headSizer = wx.BoxSizer(wx.HORIZONTAL)
        self.rowSizer = wx.BoxSizer(wx.VERTICAL)
        self.rowNeighbor = {}
        self.lastRowItem = None

        self.mainSizer.Add(self.headSizer,0,wx.EXPAND)
        self.mainSizer.Add(self.rowSizer,0,wx.EXPAND)

        self.SetSizerAndFit(self.mainSizer)
        self.mainSizer.Fit(self)

        self.SetupScrolling()

    def AddColumn(self, label, allowFiltering = False):
        self.columnText.append(label)
        self.columnHeader.append(wx.Button(self, wx.ID_ANY, size = (10,-1), label = label, style = wx.BORDER_NONE))
        self.filteredColumn.append([])

        currentColumn = self.columnNum
        self.columnHeader[-1].Bind(wx.EVT_BUTTON, lambda event : self.OnColumnClick(event, currentColumn))
        self.columnHeader[-1].Bind(wx.EVT_ENTER_WINDOW, lambda event : self.OnHeaderEnter(event, currentColumn))
        self.columnHeader[-1].Bind(wx.EVT_LEAVE_WINDOW, lambda event : self.OnHeaderLeave(event, currentColumn))
        if allowFiltering:
            self.columnHeader[-1].Bind(wx.EVT_RIGHT_UP, lambda event : self.OnColumnRightClick(event, currentColumn))
        self.columnHeader[-1].SetBackgroundColour(self.headerColor)
        self.columnHeader[-1].SetForegroundColour(self.headerFontColor)
        self.columnHeader[-1].SetFont(self.headerFont)

        self.headSizer.Add(self.columnHeader[-1], 1, wx.EXPAND)

        self.columnNum += 1

    def InsertItem(self, itemId, data):
        self.items[itemId] = Row(self, 
            itemId,
            data, 
            self.columnWidths, 
            self.GetCurrentBackgroundColors(), 
            self.rowSelectColor, 
            self.rowHoverColor, 
            self.rowFontColor, 
            self.rowFont)

        if self.lastRowItem != None:
            self.rowNeighbor[self.lastRowItem][NEXT_ITEM] = itemId
            self.rowNeighbor[itemId] = [self.lastRowItem, None]
        else:
            self.rowNeighbor[itemId] = [None, None]

        self.lastRowItem = itemId
        
        self.rowSizer.Add(self.items[itemId], 0, wx.EXPAND | wx.ALL, border=0)

        self.itemNum += 1

    def UpdateItem(self, itemId, data):
        if itemId in self.items:
            self.items[itemId].UpdateData(data)
        else:
            self.InsertItem(itemId, data)

    def DeleteItem(self, itemId):
        self.items[itemId].Hide()
        self.items.pop(itemId)
        #correct the neighbors
        self.rowNeighbor[self.rowNeighbor[itemId][PREVIOUS_ITEM]][NEXT_ITEM] =  self.rowNeighbor[itemId][NEXT_ITEM]
        self.rowNeighbor[self.rowNeighbor[itemId][NEXT_ITEM]][PREVIOUS_ITEM] =  self.rowNeighbor[itemId][PREVIOUS_ITEM]
        self.itemNum -= 1
        self.ReOrderItems()

    def DeleteAllItems(self):
        for item in self.items.itervalues():
            item.Hide()
        self.items = {}
        self.rowNeighbor = {}
        self.itemNum = 0
        self.ReOrderItems()

    def SetColumnWidth(self, widths):
        self.columnWidths = widths

        self.headSizer.Clear()
        for i in range(len(self.columnHeader)):
            self.headSizer.Add(self.columnHeader[i], self.columnWidths[i], wx.EXPAND)

        for i in range(len(self.items)):
            self.items[i].SetWidth(widths)

    def SetItemBackgroundColors(self, colors):
        self.rowColors = colors

    def SetItemSelectColor(self, color):
        self.rowSelectColor = color
        for i in range(len(self.items)):
            self.items[i].SetSelectColor(self.rowSelectColor)

    def SetItemHoverColor(self, color):
        self.rowHoverColor = color
        for i in range(len(self.items)):
            self.items[i].SetHoverColor(self.rowHoverColor)

    def SetItemFontColor(self, color):
        self.rowFontColor = color
        for i in range(len(self.items)):
            self.items[i].SetFontColor(color)

    def SetItemFont(self, font):
        self.rowFont = font
        for i in range(len(self.items)):
            self.items[i].SetFont(font)

    def SetHeaderBackgroundColor(self, color):
        self.headerColor = color
        for i in range(len(self.columnHeader)):
            self.columnHeader[i].SetBackgroundColour(self.headerColor)
            self.columnHeader[i].Refresh()

    def SetHeaderHoverColor(self, color):
        self.headerHoverColor = color

    def SetHeaderFontColor(self, color):
        self.headerFontColor = color
        for i in range(len(self.columnHeader)):
            self.columnHeader[i].SetForegroundColour(self.headerFontColor)
            self.columnHeader[i].Refresh()

    def SetHeaderFont(self, font):
        self.headerFont = font
        for i in range(len(self.columnHeader)):
            self.columnHeader[i].SetFont(self.headerFont)
            self.columnHeader[i].Refresh()

    def GetSelectedItem(self):
        return self.selectedItem

    def GetCurrentBackgroundColors(self):
        self.backgroundColorCount += 1

        return self.rowColors[self.backgroundColorCount % len(self.rowColors)]
    
    def OnHeaderEnter(self, event, column):
        self.columnHeader[column].SetBackgroundColour(self.headerHoverColor)
        self.columnHeader[column].Refresh()

    def OnHeaderLeave(self, event, column):
        self.columnHeader[column].SetBackgroundColour(self.headerColor)
        self.columnHeader[column].Refresh()

    def OnColumnRightClick(self, event, column):
        lst = set()
        for item in self.items.itervalues():
            lst.add(item.data[column])
        
        lst = list(lst)
        dlg = wx.MultiChoiceDialog(self, "Pick the categories","Category Filter", lst)

        if (dlg.ShowModal() == wx.ID_OK):
            selections = dlg.GetSelections()
            self.filteredColumn[column] = [lst[x] for x in selections]

            self.order = not self.order
            self.OnColumnClick(event,column)

        dlg.Destroy()

    def OnColumnClick(self, event, column):
        self.order = not self.order

        for i in range(len(self.columnHeader)):
            self.columnHeader[i].SetLabel(self.columnText[i])
            self.columnHeader[i].Refresh()

        if self.order:
            self.columnHeader[column].SetLabel(self.columnText[column] + " *")
        else:
            self.columnHeader[column].SetLabel(self.columnText[column] + " *")
        self.columnHeader[column].Refresh()

        self.columnOrder = column
        self.ReOrderItems()

    def OnListElementKeyDown(self, event):
        key = event.GetKeyCode()
        if key == 315 or key == 317:
            print "aaaaaaaa", self.selectedItem
            if key == 315:
                print "previous", self.rowNeighbor[self.selectedItem][PREVIOUS_ITEM]
                if self.rowNeighbor[self.selectedItem][PREVIOUS_ITEM] != None:
                    self.items[self.selectedItem].OnDeSelect(None)
                    self.items[self.rowNeighbor[self.selectedItem][PREVIOUS_ITEM]].OnSelect(None)
            elif key == 317:
                print "next", self.rowNeighbor[self.selectedItem][PREVIOUS_ITEM]
                if self.rowNeighbor[self.selectedItem][NEXT_ITEM] != None:
                    self.items[self.selectedItem].OnDeSelect(None)
                    self.items[self.rowNeighbor[self.selectedItem][NEXT_ITEM]].OnSelect(None)

        event.Skip()

    def ReOrderItems(self):
        self.mainSizer.Detach(self.rowSizer)
        self.rowSizer.Clear()

        self.rowNeighbor = {}
        self.lastRowItem = None

        sortList = []

        for item in self.items.itervalues():
            filtered = False
            for c in range(len(self.columnHeader)):
                if self.filteredColumn[c] and item.data[c] not in self.filteredColumn[c]:
                    filtered = True
                    break

            if filtered:
                item.Hide()
                continue

            item.Show()  
            
            found=False
            for j in range(len(sortList)):
                if item.dataType[self.columnOrder] == str or item.dataType[self.columnOrder] == unicode:
                    diff = cmp(sortList[j].data[self.columnOrder], item.data[self.columnOrder])
                elif item.dataType[self.columnOrder] == int:
                    diff = int(sortList[j].data[self.columnOrder]) - int(item.data[self.columnOrder]) 

                if (self.order and diff >= 0 ) or (not self.order and diff < 0):
                    sortList.insert( j, item)
                    found = True
                    break

            if found == False:
                sortList.append(item)

        for item in sortList:
            if self.lastRowItem != None:
                self.rowNeighbor[self.lastRowItem][NEXT_ITEM] = item.id
                self.rowNeighbor[item.id] = [self.lastRowItem, None]
            else:
                self.rowNeighbor[item.id] = [None, None]

            self.lastRowItem = item.id

            self.rowSizer.Add(item, 0, wx.EXPAND | wx.ALL, border=0)
            item.SetBackgroundColor(self.GetCurrentBackgroundColors())

        self.mainSizer.Add(self.rowSizer,0,wx.EXPAND)
        
        self.mainSizer.Layout()
        self.SetupScrolling()
 

class MainFrame(wx.Frame):
    def __init__(self, parent, title, size, style):
        wx.Frame.__init__(self, parent, title=title, size = size, style =style)

        self.create_main_panel()

        self.Show(True)


    def create_main_panel(self):
        self.panel = wx.Panel(self,wx.ID_ANY)

        self.listCtr = ImprovedListCtrl(self.panel)

        self.mainSizer = wx.BoxSizer(wx.HORIZONTAL)
        self.mainSizer.Add(self.listCtr,1,wx.EXPAND | wx.ALL, border = 20)

        self.panel.SetSizerAndFit(self.mainSizer)
        self.Layout()

        self.listCtr.AddColumn('Number')
        self.listCtr.AddColumn('Name')
        self.listCtr.AddColumn('Category', True)
        self.listCtr.AddColumn('Comment')
        self.listCtr.AddColumn('Quantity')

        self.listCtr.SetColumnWidth([1,2,2,5,1])
        self.listCtr.SetItemBackgroundColors(['#F7F7F7', '#FFFFFF', '#FCF8E3'])
        self.listCtr.SetItemSelectColor('#C5C7CB')
        self.listCtr.SetItemHoverColor('#EEEEEE')
        self.listCtr.SetHeaderBackgroundColor('#73879C')
        self.listCtr.SetHeaderHoverColor('#C5C7CB')
        self.listCtr.SetHeaderFontColor('#FFFFFF')
        self.listCtr.SetHeaderFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))
        self.listCtr.SetItemFontColor('#73879C')
        self.listCtr.SetItemFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))

        self.listCtr.Bind( EVT_LIST_ITEM_SELECTED, self.onItemSelected)
        self.listCtr.Bind( EVT_LIST_ITEM_RIGHT_CLICK, self.onItemRightClick)

        #self.listCtr.Bind( wx.EVT_KEY_DOWN, lambda event : self.onListKeyDown(event, data))
        self.listCtr.Bind( wx.EVT_KEY_DOWN, self.onListKeyDown)


        for i in range(len(elementBase['Values'])):
            name = elementBase['Values'][i]['Element']['name']
            category = elementBase['Values'][i]['Category']['name']
            description = elementBase['Values'][i]["Element"]['description'].encode("utf8",'ignore')
            quantity = elementBase['Values'][i]["Element"]['quantity']

            self.listCtr.InsertItem(i, [i+1, name, category, description, quantity])
        

    def onItemSelected(self, event):
        item_num = event.id
        print "onItemSelected", item_num

    def onItemRightClick(self, event):
        item_num = event.id
        print "onItemRightClick", item_num

    def onListKeyDown(self, event):
        key = event.GetKeyCode()
        if key == 388 or key == 390 or key == 43 or key == 45:
            item_num = self.listCtr.GetSelectedItem()

            if key == 388 or key == 43:
                diff = 1
                print '+'

            elif key == 390 or key == 45:
                diff = -1
                print '-'
            
            self.listCtr.UpdateItem(item_num, [item_num+1, "test", "category", "description", diff])

        if key == 68: #d
            self.listCtr.DeleteAllItems()
        if key == 65: #a
            item_num = self.listCtr.GetSelectedItem()
            self.listCtr.DeleteItem(item_num)
        
        #need to skip event
        event.Skip()



if __name__ == '__main__':
    app = wx.App(False)
    app.frame = MainFrame(None, "Improved list ctrl",
        (1300,900),
        style = wx.SYSTEM_MENU | wx.CAPTION | wx.CLOSE_BOX | wx.MINIMIZE_BOX | wx.MAXIMIZE_BOX | wx.RESIZE_BORDER)
    app.frame.Show()
    app.SetTopWindow(app.frame)

    app.MainLoop()

