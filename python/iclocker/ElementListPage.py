import wx
import wx.animate

import pymouse
import time

from ElementOptions import ElementOptions
from ImprovedListCtrl import *

LIST_COLORS = ['#F7F7F7', '#FFFFFF', '#FCF8E3']
LIST_ITEM_SELECT_COLOR = '#C5C7CB'
LIST_ITEM_HOVER_COLOR = '#EEEEEE'
LIST_ITEM_FONT_COLOR = '#73879C'

LIST_HEADER_BK_COLOR = '#73879C'
LIST_HEADER_HOVER_COLOR = '#C5C7CB'
LIST_HEADER_FONT_COLOR = '#FFFFFF'

LIST_HEADER_COLUMN_1 = 'Number'
LIST_HEADER_COLUMN_2 = 'Name'
LIST_HEADER_COLUMN_3 = 'Category'
LIST_HEADER_COLUMN_4 = 'Comment'
LIST_HEADER_COLUMN_5 = 'Quantity'

class ElementListPage(wx.Panel):
    def __init__(self, panelParent, mainFrame):
        wx.Panel.__init__(self, panelParent)
        self.mainFrame = mainFrame
        self.elementBaseThread = mainFrame.elementBaseThread

        self.popWin = None
        self.order = True
        self.filteredCategories = []
        self.collection_id = -1

        self.element_lc = ImprovedListCtrl(self)

        self.element_lc.AddColumn(LIST_HEADER_COLUMN_1)
        self.element_lc.AddColumn(LIST_HEADER_COLUMN_2)
        self.element_lc.AddColumn(LIST_HEADER_COLUMN_3, True)
        self.element_lc.AddColumn(LIST_HEADER_COLUMN_4)
        self.element_lc.AddColumn(LIST_HEADER_COLUMN_5)

        self.element_lc.SetColumnWidth([1,2,2,5,1])
        self.element_lc.SetItemBackgroundColors(LIST_COLORS)
        self.element_lc.SetItemSelectColor(LIST_ITEM_SELECT_COLOR)
        self.element_lc.SetItemHoverColor(LIST_ITEM_HOVER_COLOR)
        self.element_lc.SetHeaderBackgroundColor(LIST_HEADER_BK_COLOR)
        self.element_lc.SetHeaderHoverColor(LIST_HEADER_HOVER_COLOR)
        self.element_lc.SetHeaderFontColor(LIST_HEADER_FONT_COLOR)
        self.element_lc.SetHeaderFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))
        self.element_lc.SetItemFontColor(LIST_ITEM_FONT_COLOR)
        self.element_lc.SetItemFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))

        self.element_lc.Bind( EVT_LIST_ITEM_SELECTED, self.OnElementSelected)
        self.element_lc.Bind( EVT_LIST_ITEM_RIGHT_CLICK, self.OnElementRightClick)
        self.element_lc.Bind( wx.EVT_KEY_DOWN, self.OnListKeyDown)

        self.mainSizer = wx.BoxSizer(wx.VERTICAL)
        self.mainSizer.Add(self.element_lc,1,wx.EXPAND)

        self.SetSizerAndFit(self.mainSizer)
        self.mainSizer.Fit(self)

    def UpdateElementList(self, collectionChanged=False):
        if collectionChanged:
            self.element_lc.DeleteAllItems()

        for i in range(len(self.elementDic)):
            name = self.elementDic[i]['Element']['name']
            category = self.elementDic[i]['Category']['name']
            description = self.elementDic[i]["Element"]['description'].encode("utf8",'ignore')
            quantity = self.elementDic[i]["Element"]['quantity']

            self.element_lc.UpdateItem(i, [i+1, name, category, description, quantity])
            self.element_lc.Layout()

    def OnBaseLoaded(self, event):
        collectionChanged = False
        if self.collection_id != event.collection_id:
            collectionChanged = True

        self.collection_id = event.collection_id

        self.elementDic = self.elementBaseThread.elementBase.elements[self.collection_id]

        self.UpdateElementList(collectionChanged)
    
    def OnElementUpdate(self, event):
        if event.collection_id == self.collection_id:
            print "[ElementListPage]ElementUpdated: ",event.element_num, "Quantity: ", event.value

            name = self.elementDic[event.element_num]['Element']['name']
            category = self.elementDic[event.element_num]['Category']['name']
            description = self.elementDic[event.element_num]["Element"]['description'].encode("utf8",'ignore')
            quantity = self.elementDic[event.element_num]["Element"]['quantity']

            self.element_lc.UpdateItem(event.element_num, [event.element_num+1, name, category, description, quantity])

    def OnElementSelected(self, event):
        element_num = event.id

        self.elementBaseThread.ReadElementList.append([self.collection_id, element_num])

    def OnElementRightClick(self, event):
        element_num = event.id

        m_pos= pymouse.PyMouse().position()
        if self.popWin: 
            self.popWin.Show(False)
            self.popWin.Destroy()
        self.popWin = ElementOptions(self.GetTopLevelParent(), self.collection_id, element_num, m_pos)

        self.popWin.Show(True)

    def OnListKeyDown(self, event):
        key = event.GetKeyCode()
        if key == 388 or key == 390 or key == 43 or key == 45:
            element_num = self.element_lc.GetSelectedItem()

            if key == 388 or key == 43:
                diff = 1
                print '+'

            elif key == 390 or key == 45:
                diff = -1
                print '-'

            self.elementBaseThread.WriteElementList.append([self.collection_id, element_num, diff])
        
        #need to skip event
        event.Skip()
