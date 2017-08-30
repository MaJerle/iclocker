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
LIST_HEADER_COLUMN_3 = 'Quantity'
LIST_HEADER_COLUMN_4 = 'Description'
LIST_HEADER_COLUMN_5 = 'Elements'

class ProductOptions(wx.PopupWindow):
    def __init__(self, parent, position, product_id):
        """Constructor"""
        wx.PopupWindow.__init__(self, parent, wx.SIMPLE_BORDER)

        panel = wx.Panel(self)
        self.parent = parent
        self.panel = panel
        self.product_id = product_id
        self.position = position

        self.edit_button = wx.Button(self.panel,-1,"Edit",pos=(10,10))
        self.delete_button = wx.Button(self.panel,-1,"Delete",pos=(10,10))


        self.mainSizer = wx.BoxSizer(wx.VERTICAL)
        self.mainSizer.Add(self.edit_button,1,wx.EXPAND)
        self.mainSizer.Add(self.delete_button,1,wx.EXPAND)

        self.panel.SetSizerAndFit(self.mainSizer)
        self.mainSizer.Fit(self)

        self.edit_button.Bind(wx.EVT_LEAVE_WINDOW, self.OnLeave)
        self.edit_button.Bind(wx.EVT_ENTER_WINDOW, self.OnEnter)
        self.delete_button.Bind(wx.EVT_LEAVE_WINDOW, self.OnLeave)
        self.delete_button.Bind(wx.EVT_ENTER_WINDOW, self.OnEnter)

        self.edit_button.Bind(wx.EVT_BUTTON, self.OnEdit)
        self.delete_button.Bind(wx.EVT_BUTTON, self.OnDelete)

        self.SetPosition(self.position)

        self.enters = 0
        
        wx.CallAfter(self.Refresh)  
        

    def MouseOnPopUp(self):
        m = pymouse.PyMouse()
        mouse_pos = m.position()
        if mouse_pos[0] > self.position[0]+5 and mouse_pos[0] < (self.position[0]+self.GetSize()[0]-5):
            if mouse_pos[1] > self.position[1]+5 and mouse_pos[1] < (self.position[1]+self.GetSize()[1]-5):
                return True
        return False


    def OnLeave(self,event):
        self.enters -= 1
        #print self.enters
        if self.enters <= 0:
            #print self.MouseOnPopUp
            if self.MouseOnPopUp() == False:
                self.Show(False)
            #self.panel.Unbind(wx.EVT_LEAVE_WINDOW)
            #self.Destroy()

    def OnEnter(self,event):
        self.enters += 1
        #print self.enters

    def OnEdit(self,event):
        print "Not implemented yet"
        self.Show(False)

    def OnDelete(self,event):
        self.Show(False)
        if self.parent.elementBaseThread.elementBase.logInPassword:
            dlg = wx.PasswordEntryDialog (self.panel, 'If you really want to delete the product , enter the right password:',"Delete product")

            if (dlg.ShowModal() == wx.ID_OK):
                dlg.Destroy()
                if dlg.GetValue() == self.parent.elementBaseThread.elementBase.logInPassword:
                    self.parent.elementBaseThread.DeleteProductList.append(self.product_id)
                else:
                    msg_dlg = wx.MessageDialog(self.panel, 'Incorrect password',"Error", style = wx.ICON_ERROR | wx.OK)

                    if (msg_dlg.ShowModal() == wx.ID_OK):
                        msg_dlg.Destroy()
        else:
            msg_dlg = wx.MessageDialog(self.panel, 'Not logged it',"Error", style = wx.ICON_ERROR | wx.OK)

            if (msg_dlg.ShowModal() == wx.ID_OK):
                msg_dlg.Destroy()
        

class ProductListPage(wx.Panel):
    def __init__(self, panelParent, mainFrame):
        wx.Panel.__init__(self, panelParent)
        self.mainFrame = mainFrame
        self.elementBaseThread = mainFrame.elementBaseThread

        self.popWin = None
        self.order = True
        self.collection_id = -1

        self.product_lc = ImprovedListCtrl(self)

        self.product_lc.AddColumn(LIST_HEADER_COLUMN_1)
        self.product_lc.AddColumn(LIST_HEADER_COLUMN_2)
        self.product_lc.AddColumn(LIST_HEADER_COLUMN_3)
        self.product_lc.AddColumn(LIST_HEADER_COLUMN_4)
        self.product_lc.AddColumn(LIST_HEADER_COLUMN_5)

        self.product_lc.SetColumnWidth([1,2,2,4,2])
        self.product_lc.SetItemBackgroundColors(LIST_COLORS)
        self.product_lc.SetItemSelectColor(LIST_ITEM_SELECT_COLOR)
        self.product_lc.SetItemHoverColor(LIST_ITEM_HOVER_COLOR)
        self.product_lc.SetHeaderBackgroundColor(LIST_HEADER_BK_COLOR)
        self.product_lc.SetHeaderHoverColor(LIST_HEADER_HOVER_COLOR)
        self.product_lc.SetHeaderFontColor(LIST_HEADER_FONT_COLOR)
        self.product_lc.SetHeaderFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))
        self.product_lc.SetItemFontColor(LIST_ITEM_FONT_COLOR)
        self.product_lc.SetItemFont(wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD))

        self.product_lc.Bind( EVT_LIST_ITEM_SELECTED, self.onProductSelected)
        self.product_lc.Bind( EVT_LIST_ITEM_RIGHT_CLICK, self.onProductRightClick)

        self.mainSizer = wx.BoxSizer(wx.VERTICAL)
        self.mainSizer.Add(self.product_lc,1,wx.EXPAND)

        self.SetSizerAndFit(self.mainSizer)
        self.mainSizer.Fit(self)

    def UpdateList(self, collectionChanged=False):
        if collectionChanged:
            self.product_lc.DeleteAllItems()

        for i in range(len(self.productDic)):
            name = self.productDic[i]['Product']['name']
            quantity = self.productDic[i]['Product']['quantity']
            description = self.productDic[i]['Product']['description'].encode("utf8",'ignore')
            element = self.productDic[i]["Product"]['elements_count_all']

            self.product_lc.UpdateItem(i, [i+1, name, quantity, description, element])
            self.product_lc.Layout()

    def OnBaseLoaded(self, event):
        collectionChanged = False
        if self.collection_id != event.collection_id:
            collectionChanged = True

        self.collection_id = event.collection_id

        self.productDic = self.elementBaseThread.elementBase.products[self.collection_id]

        self.UpdateList(collectionChanged)
    
    def onProductSelected(self, event):
        product_num = event.id

        #self.elementBaseThread.UpdateProductsList.append([self.collection_id, self.productPages[self.page_selected].product_id])

    def onProductRightClick(self, event):
        product_num = event.id

        m_pos= pymouse.PyMouse().position()
        if self.popWin: 
            self.popWin.Show(False)
            self.popWin.Destroy()
        self.popWin = ProductOptions(self.GetTopLevelParent(), self.collection_id, product_num, m_pos)

        self.popWin.Show(True)

    def OnNotebookRightClick(self, event):
        page_right_clicked = self.notebook.HitTest(event.GetPosition())[0]

        m_pos= pymouse.PyMouse().position()
        if self.popWin: 
            self.popWin.Show(False)
            self.popWin.Destroy()
        self.popWin = ProductOptions(self.GetTopLevelParent(), m_pos, self.productPages[page_right_clicked].product_id)

        self.popWin.Show(True)


