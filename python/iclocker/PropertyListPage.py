import wx
import wx.animate

import pymouse
import time

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
LIST_HEADER_COLUMN_3 = 'Description'
LIST_HEADER_COLUMN_4 = 'Unit'
LIST_HEADER_COLUMN_5 = 'Data type'

DATA_TYPE_STRINGS = ["Unknown(0)", "String", "Unknown(2)", "File upload", "Unknown(4)"]

class PropertyListPage(wx.Panel):
    def __init__(self, panelParent, mainFrame):
        wx.Panel.__init__(self, panelParent)
        self.mainFrame = mainFrame
        self.elementBaseThread = mainFrame.elementBaseThread

        self.popWin = None
        self.order = True
        self.collection_id = -1
        
        self.property_lc = ImprovedListCtrl(self)

        self.property_lc.AddColumn(LIST_HEADER_COLUMN_1)
        self.property_lc.AddColumn(LIST_HEADER_COLUMN_2)
        self.property_lc.AddColumn(LIST_HEADER_COLUMN_3)
        self.property_lc.AddColumn(LIST_HEADER_COLUMN_4)
        self.property_lc.AddColumn(LIST_HEADER_COLUMN_5, True)

        self.property_lc.SetColumnWidth([1,2,4,2,2])
        self.property_lc.SetItemBackgroundColors(LIST_COLORS)
        self.property_lc.SetItemSelectColor(LIST_ITEM_SELECT_COLOR)
        self.property_lc.SetItemHoverColor(LIST_ITEM_HOVER_COLOR)
        self.property_lc.SetHeaderBackgroundColor(LIST_HEADER_BK_COLOR)
        self.property_lc.SetHeaderHoverColor(LIST_HEADER_HOVER_COLOR)
        self.property_lc.SetHeaderFontColor(LIST_HEADER_FONT_COLOR)
        self.property_lc.SetHeaderFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))
        self.property_lc.SetItemFontColor(LIST_ITEM_FONT_COLOR)
        self.property_lc.SetItemFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))

        self.property_lc.Bind( EVT_LIST_ITEM_SELECTED, self.onPropertySelected)
        self.property_lc.Bind( EVT_LIST_ITEM_RIGHT_CLICK, self.onPropertyRightClick)

        self.mainSizer = wx.BoxSizer(wx.VERTICAL)
        self.mainSizer.Add(self.property_lc,1,wx.EXPAND)

        self.SetSizerAndFit(self.mainSizer)
        self.mainSizer.Fit(self)

    def UpdateList(self, collectionChanged=False):
        if collectionChanged:
            self.property_lc.DeleteAllItems()

        for i in range(len(self.propertyDic)):
            name = self.propertyDic[i]['Property']['name']
            description = self.propertyDic[i]['Property']['description'].encode("utf8",'ignore')
            unit = self.propertyDic[i]["Property"]['unit']
            data_type = DATA_TYPE_STRINGS[self.propertyDic[i]["Property"]['data_type']]

            self.property_lc.UpdateItem(i, [i+1, name, description, unit, data_type])
            self.property_lc.Layout()

    def OnBaseLoaded(self, event):
        collectionChanged = False
        if self.collection_id != event.collection_id:
            collectionChanged = True

        self.collection_id = event.collection_id

        self.propertyDic = self.elementBaseThread.elementBase.properties[self.collection_id]

        self.UpdateList(collectionChanged)
    
    def OnPropertyUpdate(self, event):
        if event.collection_id == self.collection_id:
   
            name = self.propertyDic[event.element_num]['Property']['name']
            description = self.propertyDic[event.element_num]['Property']['description'].encode("utf8",'ignore')
            unit = self.propertyDic[event.element_num]["Property"]['unit']
            data_type = DATA_TYPE_STRINGS[self.propertyDic[event.element_num]["Property"]['data_type']]

            self.property_lc.UpdateItem(event.element_num, [event.element_num+1, name, description, unit, data_type])

    def onPropertySelected(self, event):
        element_num = event.id

        self.elementBaseThread.ReadElementList.append([self.collection_id, element_num])

    def onPropertyRightClick(self, event):
        element_num = event.id

        m_pos= pymouse.PyMouse().position()
        if self.popWin: 
            self.popWin.Show(False)
            self.popWin.Destroy()
        self.popWin = ElementOptions(self.GetTopLevelParent(), self.collection_id, element_num, m_pos)

        self.popWin.Show(True)

