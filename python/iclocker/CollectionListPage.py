import wx

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
LIST_HEADER_COLUMN_4 = 'Categories'
LIST_HEADER_COLUMN_5 = 'Elements'
LIST_HEADER_COLUMN_6 = 'Properties'
LIST_HEADER_COLUMN_7 = 'Products'
LIST_HEADER_COLUMN_8 = 'Orders'

class CollectionListPage(wx.Panel):
    def __init__(self, panelParent, mainFrame):
        wx.Panel.__init__(self, panelParent)
        self.mainFrame = mainFrame
        self.elementBaseThread = mainFrame.elementBaseThread

        self.popWin = None
        self.order = True

        self.collection_lc = ImprovedListCtrl(self)

        self.collection_lc.AddColumn(LIST_HEADER_COLUMN_1)
        self.collection_lc.AddColumn(LIST_HEADER_COLUMN_2)
        self.collection_lc.AddColumn(LIST_HEADER_COLUMN_3)
        self.collection_lc.AddColumn(LIST_HEADER_COLUMN_4)
        self.collection_lc.AddColumn(LIST_HEADER_COLUMN_5)
        self.collection_lc.AddColumn(LIST_HEADER_COLUMN_6)
        self.collection_lc.AddColumn(LIST_HEADER_COLUMN_7)
        self.collection_lc.AddColumn(LIST_HEADER_COLUMN_8)

        self.collection_lc.SetColumnWidth([1,2,3,1,1,1,1,1])
        self.collection_lc.SetItemBackgroundColors(LIST_COLORS)
        self.collection_lc.SetItemSelectColor(LIST_ITEM_SELECT_COLOR)
        self.collection_lc.SetItemHoverColor(LIST_ITEM_HOVER_COLOR)
        self.collection_lc.SetHeaderBackgroundColor(LIST_HEADER_BK_COLOR)
        self.collection_lc.SetHeaderHoverColor(LIST_HEADER_HOVER_COLOR)
        self.collection_lc.SetHeaderFontColor(LIST_HEADER_FONT_COLOR)
        self.collection_lc.SetHeaderFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))
        self.collection_lc.SetItemFontColor(LIST_ITEM_FONT_COLOR)
        self.collection_lc.SetItemFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))

        self.collection_lc.Bind( EVT_LIST_ITEM_SELECTED, self.onCollectionSelected)
        self.collection_lc.Bind( EVT_LIST_ITEM_RIGHT_CLICK, self.onCollectionRightClick)

        self.mainSizer = wx.BoxSizer(wx.VERTICAL)
        self.mainSizer.Add(self.collection_lc,1,wx.EXPAND)

        self.SetSizerAndFit(self.mainSizer)
        self.mainSizer.Fit(self)

    def UpdateList(self):
        #self.collection_lc.DeleteAllItems()

        self.collectionDic = self.elementBaseThread.elementBase.collections
        for i in range(len(self.collectionDic)):
            name = self.collectionDic[i]['name']
            description = self.collectionDic[i]['description']
            categories_count = self.collectionDic[i]['categories_count']
            elements_count = self.collectionDic[i]['elements_count']
            properties_count = self.collectionDic[i]['properties_count']
            products_count = self.collectionDic[i]['products_count']
            elementorders_count= self.collectionDic[i]['elementorders_count']

            self.collection_lc.UpdateItem(i, [i+1, name, description, categories_count, elements_count, properties_count, products_count, elementorders_count])
            self.collection_lc.Layout()

    def OnBaseLoaded(self, event):
        self.UpdateList()		

    def onCollectionSelected(self, event):
        collection_num = event.id

    def onCollectionRightClick(self, event):
        collection_num = event.id
