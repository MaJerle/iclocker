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
LIST_HEADER_COLUMN_3 = 'Status'
LIST_HEADER_COLUMN_4 = 'Created'
LIST_HEADER_COLUMN_5 = 'Ordered'
LIST_HEADER_COLUMN_6 = 'Element counts'

ORDER_STATUS_STRINGS = ["Canceled", "Open", "Ordered", "Unknown(3)"]

class OrderListPage(wx.Panel):
    def __init__(self, panelParent, mainFrame):
        wx.Panel.__init__(self, panelParent)
        self.mainFrame = mainFrame
        self.elementBaseThread = mainFrame.elementBaseThread

        self.popWin = None
        self.order = True
        self.collection_id = -1

        self.order_lc = ImprovedListCtrl(self)

        self.order_lc.AddColumn(LIST_HEADER_COLUMN_1)
        self.order_lc.AddColumn(LIST_HEADER_COLUMN_2)
        self.order_lc.AddColumn(LIST_HEADER_COLUMN_3, True)
        self.order_lc.AddColumn(LIST_HEADER_COLUMN_4)
        self.order_lc.AddColumn(LIST_HEADER_COLUMN_5)
        self.order_lc.AddColumn(LIST_HEADER_COLUMN_6)

        self.order_lc.SetColumnWidth([1,2,1,2,2,2])
        self.order_lc.SetItemBackgroundColors(LIST_COLORS)
        self.order_lc.SetItemSelectColor(LIST_ITEM_SELECT_COLOR)
        self.order_lc.SetItemHoverColor(LIST_ITEM_HOVER_COLOR)
        self.order_lc.SetHeaderBackgroundColor(LIST_HEADER_BK_COLOR)
        self.order_lc.SetHeaderHoverColor(LIST_HEADER_HOVER_COLOR)
        self.order_lc.SetHeaderFontColor(LIST_HEADER_FONT_COLOR)
        self.order_lc.SetHeaderFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))
        self.order_lc.SetItemFontColor(LIST_ITEM_FONT_COLOR)
        self.order_lc.SetItemFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))

        self.order_lc.Bind( EVT_LIST_ITEM_SELECTED, self.onOrderSelected)
        self.order_lc.Bind( EVT_LIST_ITEM_RIGHT_CLICK, self.onOrderRightClick)

        self.mainSizer = wx.BoxSizer(wx.VERTICAL)
        self.mainSizer.Add(self.order_lc,1,wx.EXPAND)

        self.SetSizerAndFit(self.mainSizer)
        self.mainSizer.Fit(self)

    def UpdateList(self, collectionChanged=False):
        if collectionChanged:
            self.order_lc.DeleteAllItems()


        for i in range(len(self.orderDic)):
            name = self.orderDic[i]['Elementorder']['name']
            status = ORDER_STATUS_STRINGS[self.orderDic[i]['Elementorder']['status']]
            dateCreated = self.orderDic[i]['Elementorder']['datecreated']
            dateOrdered = self.orderDic[i]["Elementorder"]['dateordered']
            orderElementsCount = self.orderDic[i]["Elementorder"]['orderelements_count']

            self.order_lc.UpdateItem(i, [i+1, name, status, dateCreated, dateOrdered, orderElementsCount])
            self.order_lc.Layout()

    def OnBaseLoaded(self, event):
        collectionChanged = False
        if self.collection_id != event.collection_id:
            collectionChanged = True

        self.collection_id = event.collection_id

        self.orderDic = self.elementBaseThread.elementBase.orders[self.collection_id]

        self.UpdateList(collectionChanged)
    
    def onOrderSelected(self, event):
        order_num = event.id

    def onOrderRightClick(self, event):
        order_num = event.id


