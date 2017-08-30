import wx
import wx.lib.newevent
import wx.lib.platebtn

import os

from CollectionListPage import CollectionListPage
from CategoryListPage import CategoryListPage
from ElementListPage import ElementListPage
from ProductListPage import ProductListPage
from PropertyListPage import PropertyListPage
from OrderListPage import OrderListPage


LOGONAME = "logo.png"
LOGOSIZE = (40,40)

MAIN_SIDE_PANEL_COLOR = '#41454A'
MAIN_BACK_PANEL_COLOR = '#F7F7F7'
MAIN_TOP_PANEL_COLOR = '#FFFFFF'

MAIN_SIDE_TITLE = 'IC Locker'
MAIN_SIDE_TITLE_FONT = [20, wx.FONTFAMILY_DEFAULT, wx.FONTSTYLE_NORMAL, wx.FONTWEIGHT_BOLD]
MAIN_SIDE_TXT_COLOR = '#FFFFFF'

MAIN_SIDE_TREE_COLLECTION = 'Collection'
MAIN_SIDE_TREE_COLLECTION_FONT = [12, wx.FONTFAMILY_DEFAULT, wx.FONTSTYLE_NORMAL, wx.FONTWEIGHT_NORMAL]


MAIN_TOP_COLLECTION = 'Collections'
MAIN_TOP_CATEGORIES = "Categories"
MAIN_TOP_ELEMENTS = 'Elements'
MAIN_TOP_PRODUCTS = 'Products'
MAIN_TOP_PROPERTIES = 'Properties'
MAIN_TOP_ORDERS = "Orders"
MAIN_TOP_ADD = '+ Add'
MAIN_TOP_TITLE_FONT = [16, wx.FONTFAMILY_DEFAULT, wx.FONTSTYLE_NORMAL, wx.FONTWEIGHT_BOLD]
MAIN_TOP_LIGHT_TXT_COLOR = '#C5C7CB'
MAIN_TOP_TXT_COLOR = '#73879C'
MAIN_TOP_DARK_TXT_COLOR = '#73879C'



class Collection():
    COLLECTION = 0
    CATEGORIES = 1
    ELEMENTS = 2
    PRODUCTS = 3
    PROPERTIES = 4


def setTextStyle(text, font, color):
    text.SetFont(wx.Font(font[0], 
        family = font[1], 
        style = font[2], 
        weight = font[3]))
    text.SetForegroundColour(color)

class TopPanel(wx.Panel):
    def __init__(self, panelParent, parent):
        wx.Panel.__init__(self, panelParent, style = wx.SIMPLE_BORDER)
        self.parent = parent
        self.panel = self

        self.elementBaseThread = self.parent.elementBaseThread

        self.create_panel()

    def create_panel(self):
        self.SetBackgroundColour(MAIN_TOP_PANEL_COLOR)

        self.topTitle = wx.StaticText(self.panel, label=MAIN_TOP_COLLECTION)
        setTextStyle(self.topTitle, MAIN_TOP_TITLE_FONT, MAIN_TOP_TXT_COLOR)

        self.addButton = wx.Button(self, wx.ID_ANY, size = (100,-1), label = MAIN_TOP_ADD, style = wx.BORDER_NONE)
        self.addButton.Bind(wx.EVT_BUTTON, self.OnButtonClick)
        self.addButton.Bind(wx.EVT_ENTER_WINDOW, self.OnButtonEnter)
        self.addButton.Bind(wx.EVT_LEAVE_WINDOW, self.OnButtonLeave)

        setTextStyle(self.addButton, MAIN_TOP_TITLE_FONT, MAIN_TOP_LIGHT_TXT_COLOR)
        self.addButton.SetBackgroundColour(MAIN_TOP_PANEL_COLOR)

        self.titleSizer = wx.BoxSizer(wx.HORIZONTAL)

        self.titleSizer.AddSpacer(20)
        self.titleSizer.Add(self.topTitle)
        self.titleSizer.Add((0,0),1,wx.EXPAND)
        self.titleSizer.Add(self.addButton)
        self.titleSizer.AddSpacer(20)

        self.line = wx.StaticLine(self.panel, -1, size=(1,3), style=wx.LI_HORIZONTAL)
        self.line.SetBackgroundColour(MAIN_TOP_LIGHT_TXT_COLOR)


        self.listPanel = wx.Panel(self.panel)

        self.collectionList = CollectionListPage(self.listPanel, self.parent)
        self.elementList = ElementListPage(self.listPanel, self.parent)
        self.categoryList = CategoryListPage(self.listPanel, self.parent)
        self.productList = ProductListPage(self.listPanel, self.parent)
        self.propertyList = PropertyListPage(self.listPanel, self.parent)
        self.orderList = OrderListPage(self.listPanel, self.parent)


        self.TopPanelChange(MAIN_TOP_COLLECTION, None)

        self.mainSizer = wx.BoxSizer(wx.VERTICAL)

        self.mainSizer.AddSpacer(20)
        self.mainSizer.Add(self.titleSizer,0,wx.EXPAND)
        self.mainSizer.Add(self.line,0, wx.EXPAND | wx.ALL, border = 20)
        self.mainSizer.Add(self.listPanel, 1, wx.EXPAND | wx.ALL, border = 20)
        self.mainSizer.AddSpacer(20)


        self.SetSizerAndFit(self.mainSizer)
        self.mainSizer.Fit(self)

    def OnButtonClick(self, event):
        print "add button clicked"

    def OnButtonEnter(self, event):
        self.addButton.SetForegroundColour(MAIN_TOP_TXT_COLOR)

    def OnButtonLeave(self, event):
        self.addButton.SetForegroundColour(MAIN_TOP_LIGHT_TXT_COLOR)

    def TopPanelChange(self, collection, sub):

        self.collectionList.Hide()
        self.elementList.Hide()
        self.categoryList.Hide()
        self.productList.Hide()
        self.propertyList.Hide()
        self.orderList.Hide()

        if sub == None:
            self.topTitle.SetLabel(MAIN_TOP_COLLECTION)
            self.selectedList = self.collectionList
        else:
            for baseCollection in self.elementBaseThread.elementBase.collections.itervalues():
                if collection == baseCollection['name']:
                    baseCollectionId = baseCollection['id']
                    
            self.topTitle.SetLabel(sub)
            if sub == MAIN_TOP_ELEMENTS:
                self.selectedList = self.elementList
                self.elementBaseThread.LoadElementList.append(baseCollectionId)
            elif sub == MAIN_TOP_CATEGORIES:
                self.selectedList = self.categoryList
                self.elementBaseThread.LoadCategoryList.append(baseCollectionId)
            elif sub == MAIN_TOP_PROPERTIES:
                self.selectedList = self.propertyList
                self.elementBaseThread.LoadPropertyList.append(baseCollectionId)
            elif sub == MAIN_TOP_PRODUCTS:
                self.selectedList = self.productList
                self.elementBaseThread.LoadProductList.append(baseCollectionId)
            elif sub == MAIN_TOP_ORDERS:
                self.selectedList = self.orderList
                self.elementBaseThread.LoadOrderList.append(baseCollectionId)

        self.selectedList.Show()

        self.listPanelSizer = wx.BoxSizer(wx.VERTICAL)
        self.listPanelSizer.Add(self.selectedList, 1, wx.EXPAND)

        self.listPanel.SetSizer(self.listPanelSizer)

        self.selectedList.SetSizerAndFit(self.selectedList.mainSizer)

        self.Layout()


class MainPanel(wx.Panel):
    def __init__(self, parent):
        wx.Panel.__init__(self, parent)
        self.parent = parent
        self.panel = self

        self.elementBaseThread = self.parent.elementBaseThread

        self.page_selected = None

        self.create_side_panel()
        self.create_main_panel()
        self.combine_panels()

    def create_side_panel(self):
        self.sidePanel = wx.Panel(self.panel)
        self.sidePanel.SetBackgroundColour(MAIN_SIDE_PANEL_COLOR)

        image = wx.ImageFromBitmap(self.parent.logo)
        image = image.Scale(LOGOSIZE[0],LOGOSIZE[1], wx.IMAGE_QUALITY_HIGH)
        self.logoIcon  = wx.StaticBitmap(self.sidePanel, wx.ID_ANY, wx.BitmapFromImage(image))

        self.siteName = wx.StaticText(self.sidePanel, label=MAIN_SIDE_TITLE)
        setTextStyle(self.siteName, MAIN_SIDE_TITLE_FONT, MAIN_SIDE_TXT_COLOR)

        self.logoSizer = wx.BoxSizer(wx.HORIZONTAL)

        self.logoSizer.AddSpacer(20)
        self.logoSizer.Add(self.logoIcon,0,wx.EXPAND)
        self.logoSizer.AddSpacer(10)
        self.logoSizer.Add(self.siteName,0,wx.CENTER)
        self.logoSizer.AddSpacer(20)

        self.collectionTreeCtrl = wx.TreeCtrl(self.sidePanel, id=wx.ID_ANY, pos=wx.DefaultPosition, size=wx.DefaultSize,
            style=wx.TR_DEFAULT_STYLE | wx.TR_TWIST_BUTTONS | wx.TR_LINES_AT_ROOT, validator=wx.DefaultValidator,
            name=wx.TreeCtrlNameStr)
        setTextStyle(self.collectionTreeCtrl, MAIN_SIDE_TREE_COLLECTION_FONT, MAIN_SIDE_TXT_COLOR)
        self.collectionTreeCtrl.SetBackgroundColour(MAIN_SIDE_PANEL_COLOR)
        self.collectionTreeCtrl.Bind( wx.EVT_TREE_SEL_CHANGED, self.onCollectionTreeSelect)
        self.collectionRootTree = self.collectionTreeCtrl.AddRoot(MAIN_SIDE_TREE_COLLECTION) 
        
        self.sideSizer = wx.BoxSizer(wx.VERTICAL)

        self.sideSizer.AddSpacer(20)
        self.sideSizer.Add(self.logoSizer,0,wx.EXPAND)
        self.sideSizer.AddSpacer(20)
        self.sideSizer.Add(self.collectionTreeCtrl,1,wx.EXPAND)

        self.sidePanel.SetSizerAndFit(self.sideSizer)
        self.sideSizer.Fit(self)

    def create_main_panel(self):
        self.mainPanel = wx.Panel(self.panel)
        self.mainPanel.SetBackgroundColour(MAIN_BACK_PANEL_COLOR)

        self.font = wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_NORMAL)
        self.fontB = wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD)
        self.fontS = wx.Font(10, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_NORMAL)

        self.topPanel = TopPanel(self.mainPanel, self)

        self.mainSizer = wx.BoxSizer(wx.VERTICAL)
        self.mainSizer.Add(self.topPanel, 1, wx.EXPAND | wx.ALL, border=20)

        self.mainPanel.SetSizerAndFit(self.mainSizer)
        self.mainSizer.Fit(self)

    def combine_panels(self):
        self.combinedSizer = wx.BoxSizer(wx.HORIZONTAL)
        self.combinedSizer.Add(self.sidePanel,0,wx.EXPAND)
        self.combinedSizer.Add(self.mainPanel,1,wx.EXPAND)

        self.panel.SetSizer(self.combinedSizer)
        self.panel.Layout()


    def onCollectionTreeSelect(self, event):
        for collection_iter in range(len(self.collectionTree)):
            if event.GetItem() == self.collectionTree[collection_iter]:
                collection = self.collectionTreeCtrl.GetItemText(event.GetItem())
                self.topPanel.TopPanelChange(collection, None)

                self.elementBaseThread.loadCollectionList = True
                return
            for sub_iter in range(len(self.subCollectionTree[collection_iter])):
                if event.GetItem() == self.subCollectionTree[collection_iter][sub_iter]:
                    collection = self.collectionTreeCtrl.GetItemText(self.collectionTree[collection_iter])
                    sub = self.collectionTreeCtrl.GetItemText(event.GetItem())
                    self.topPanel.TopPanelChange(collection, sub)
                    return


    def OnBaseLoaded(self, event):
        self.collectionTreeCtrl.DeleteChildren(self.collectionRootTree)
        self.collectionTree = []
        self.subCollectionTree = []

        for collection in self.elementBaseThread.elementBase.collections.itervalues():
            self.collectionTree.append(self.collectionTreeCtrl.AppendItem(self.collectionRootTree, collection['name']))
            self.subCollectionTree.append([])
            self.subCollectionTree[-1].append(self.collectionTreeCtrl.AppendItem(self.collectionTree[-1], MAIN_TOP_CATEGORIES))
            self.subCollectionTree[-1].append(self.collectionTreeCtrl.AppendItem(self.collectionTree[-1], MAIN_TOP_ELEMENTS))
            self.subCollectionTree[-1].append(self.collectionTreeCtrl.AppendItem(self.collectionTree[-1], MAIN_TOP_PROPERTIES))
            self.subCollectionTree[-1].append(self.collectionTreeCtrl.AppendItem(self.collectionTree[-1], MAIN_TOP_PRODUCTS))
            self.subCollectionTree[-1].append(self.collectionTreeCtrl.AppendItem(self.collectionTree[-1], MAIN_TOP_ORDERS))

        self.collectionTreeCtrl.ExpandAll()

        self.topPanel.collectionList.OnBaseLoaded(event)
        #self.topPanel.productListPage.OnBaseLoaded(event)

    def OnCollectionsLoaded(self, event):
        self.topPanel.collectionList.OnBaseLoaded(event)

    def OnElementsLoaded(self, event):
        self.topPanel.elementList.OnBaseLoaded(event)

    def OnCategoryLoaded(self, event):
        self.topPanel.categoryList.OnBaseLoaded(event)
    
    def OnPropertyLoaded(self, event):
        self.topPanel.propertyList.OnBaseLoaded(event)
    
    def OnProductLoaded(self,event):
        self.topPanel.productList.OnBaseLoaded(event)
    
    def OnOrderLoaded(self, event):
		self.topPanel.orderList.OnBaseLoaded(event)
    
    def OnElementUpdate(self, event):
        self.topPanel.elementList.OnElementUpdate(event)




class MainTestFrame(wx.Frame):
    def __init__(self, parent, title, size, style):
        self.l = wx.Locale(wx.LANGUAGE_ENGLISH)
        wx.Frame.__init__(self, parent, title=title, size = size, style =style)
        
        self.logo = wx.Bitmap(LOGONAME)

        self.elementBaseThread = None

        self.Show(True)

        self.mainPanel = MainPanel(self)
        self.mainPanel.Show()

        self.sizer = wx.BoxSizer(wx.VERTICAL)
        self.sizer.Add(self.mainPanel, 1, wx.EXPAND | wx.ALL)
        self.SetSizer(self.sizer)

        self.mainPanel.SetSizerAndFit(self.mainPanel.combinedSizer)

        self.Layout()


if __name__ == '__main__':
    app = wx.App(False)
    app.frame = MainTestFrame(None, "IC Locker (only MainPanel)",
        (1400,700),
        style = wx.SYSTEM_MENU | wx.CAPTION | wx.CLOSE_BOX | wx.MINIMIZE_BOX | wx.MAXIMIZE_BOX | wx.RESIZE_BORDER)
    app.frame.Show()
    app.SetTopWindow(app.frame)

    app.MainLoop()
        
