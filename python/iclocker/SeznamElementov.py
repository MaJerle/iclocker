import wx
import wx.lib.newevent
import wx.lib.mixins.listctrl as listmix
import os

import operator
import pymouse
import time
import webbrowser
import threading 


from LoginPanel import LoginPanel
from MainPanel import MainPanel

from ElementBase import ElementBase

from AddElementToBase import AddElementToBase
from AltiumToBase import AltiumToBaseFrame

from pics import *

LOGONAME = "logo.png"

BaseLoadedEvent, EVT_BASE_LOADED_EVENT = wx.lib.newevent.NewEvent()
CollectionListLoadedEvent, EVT_COLLECTION_LIST_LOADED_EVENT = wx.lib.newevent.NewEvent()
ElementListLoadedEvent, EVT_ELEMENT_LIST_LOADED_EVENT = wx.lib.newevent.NewEvent()
CategoryListLoadedEvent, EVT_CATEGORY_LIST_LOADED_EVENT = wx.lib.newevent.NewEvent()
PropertyListLoadedEvent, EVT_PROPERTY_LIST_LOADED_EVENT = wx.lib.newevent.NewEvent()
ProductListLoadedEvent, EVT_PRODUCT_LIST_LOADED_EVENT = wx.lib.newevent.NewEvent()
OrderListLoadedEvent, EVT_ORDER_LIST_LOADED_EVENT = wx.lib.newevent.NewEvent()


ElementUpdateEvent, EVT_ELEMENT_UPDATE_EVENT = wx.lib.newevent.NewEvent()

ElementBaseLoginEvent, EVT_ELEMENT_BASE_LOGIN_EVENT = wx.lib.newevent.NewEvent()


class ElementBaseThread(threading.Thread):
	def __init__(self, wx):
		threading.Thread.__init__(self)
		self._stop = threading.Event()
		self.wx = wx

		self.elementBase = ElementBase()


		self.loadBaseFlag = False
		self.loginFlag = False

		self.loadCollectionList = False
		self.LoadElementList = []
		self.LoadCategoryList = []
		self.LoadPropertyList = []
		self.LoadProductList = []
		self.LoadOrderList = []

		self.ReadElementList = []
		self.WriteElementList = []
		self.DeleteElementList = []
		self.AddElementList = []
		self.EditElementList = []

		self.AddProductList = []
		self.DeleteProductList = []

	def run(self):
		while True:
			time.sleep(0.02)

			if self.loginFlag:
				self.loggedIn = self.elementBase.Login(self.loginFlag[0],self.loginFlag[1])

				print self.loggedIn
				evt = ElementBaseLoginEvent(success = self.elementBase.loggedIn)
				wx.PostEvent(self.wx, evt)

				self.loginFlag = False

			if self.elementBase.loggedIn:
				if self.loadBaseFlag:
					loadStatus = self.elementBase.loadBase()

					evt = BaseLoadedEvent(success = loadStatus)
					wx.PostEvent(self.wx, evt)

					self.loadBaseFlag = False

				if self.loadCollectionList:
					loadStatus = self.elementBase.getCollections()

					evt = CollectionListLoadedEvent(success = loadStatus)
					wx.PostEvent(self.wx, evt)

					self.loadCollectionList = False

				if len(self.LoadElementList):
					collection_id = self.LoadElementList.pop(0)
					self.elementBase.getElements(collection_id)

					evt = ElementListLoadedEvent(success = loadStatus, collection_id = collection_id)
					wx.PostEvent(self.wx, evt)

				if len(self.LoadCategoryList):
					collection_id = self.LoadCategoryList.pop(0)
					self.elementBase.getCategories(collection_id)

					evt = CategoryListLoadedEvent(success = loadStatus, collection_id = collection_id)
					wx.PostEvent(self.wx, evt)
				
				if len(self.LoadPropertyList):
					collection_id = self.LoadPropertyList.pop(0)
					self.elementBase.getProperties(collection_id)

					evt = PropertyListLoadedEvent(success = loadStatus, collection_id = collection_id)
					wx.PostEvent(self.wx, evt)

				if len(self.LoadProductList):
					collection_id = self.LoadProductList.pop(0)
					self.elementBase.getProducts(collection_id)

					evt = ProductListLoadedEvent(success = loadStatus, collection_id = collection_id)
					wx.PostEvent(self.wx, evt)

				if len(self.LoadOrderList):
					collection_id = self.LoadOrderList.pop(0)
					self.elementBase.getOrders(collection_id)

					evt = OrderListLoadedEvent(success = loadStatus, collection_id = collection_id)
					wx.PostEvent(self.wx, evt)

				if len(self.ReadElementList):
					[collection_id, element_num] = self.ReadElementList.pop(0)
					value = self.elementBase.ReadQuantity(collection_id, element_num)

					if isinstance( value, int ):
						evt = ElementUpdateEvent(collection_id = collection_id, element_num = element_num, value = value)
						wx.PostEvent(self.wx, evt)

				if len(self.WriteElementList):
					[collection_id, element_num, diff] = self.WriteElementList.pop(0)
					value = self.elementBase.ReadQuantity(collection_id, element_num)
					self.elementBase.UpdateQuantity(collection_id, element_num, str(int(value)+diff))
					value = self.elementBase.ReadQuantity(collection_id, element_num)

					if isinstance( value, int ):
						evt = ElementUpdateEvent(collection_id = collection_id, element_num = element_num, value = value)
						wx.PostEvent(self.wx, evt)

				if len(self.DeleteElementList):
					element_num = self.DeleteElementList.pop(0)
					self.elementBase.DeleteElement(element_num)

				if len(self.AddElementList):
					[category_id,name,quantity,description,properties] = self.AddElementList.pop(0)
					self.elementBase.AddElement(category_id,name,quantity,description,properties)

				if len(self.AddProductList):
					[name,description,elements] = self.AddProductList.pop(0)
					self.elementBase.AddProduct(name,description,elements)

				if len(self.DeleteProductList):
					product_id = self.DeleteProductList.pop(0)
					self.elementBase.DeleteProduct(product_id)

				if len(self.EditElementList):
					[element_id, name, quantity, comment, category_id, properties] = self.EditElementList.pop(0)
					self.elementBase.EditElement(element_id, name, quantity, comment, category_id, properties)


				if self.elementBase.loggedIn == False:
					evt = ElementBaseLoginEvent(success = self.elementBase.loggedIn)
					wx.PostEvent(self.wx, evt)



class MainFrame(wx.Frame):
	def __init__(self, parent, title, size, style):
		self.l = wx.Locale(wx.LANGUAGE_ENGLISH)
		wx.Frame.__init__(self, parent, title=title, size = size, style =style)
		
		self.logo = wx.Bitmap(LOGONAME)

		self.Show(True)

		self.elementBaseThread = ElementBaseThread(self)

		self.mainPanel = MainPanel(self)
		self.mainPanel.Hide()
		self.loginPanel = LoginPanel(self)
		self.loginPanel.Hide()

		self.Bind(EVT_BASE_LOADED_EVENT, self.OnBaseLoaded)
		self.Bind(EVT_COLLECTION_LIST_LOADED_EVENT, self.OnCollectionsLoaded)
		self.Bind(EVT_ELEMENT_LIST_LOADED_EVENT, self.OnElementsLoaded)
		self.Bind(EVT_CATEGORY_LIST_LOADED_EVENT, self.OnCategoryLoaded)
		self.Bind(EVT_PROPERTY_LIST_LOADED_EVENT, self.OnPropertyLoaded)
		self.Bind(EVT_PRODUCT_LIST_LOADED_EVENT, self.OnProductLoaded)
		self.Bind(EVT_ORDER_LIST_LOADED_EVENT, self.OnOrderLoaded)
		

		self.Bind(EVT_ELEMENT_UPDATE_EVENT, self.OnElementUpdate)
		self.Bind(EVT_ELEMENT_BASE_LOGIN_EVENT, self.OnElementBaseLogin)

		self.elementBaseThread.start()

		self.OnChangePanel(True)

	def OnBaseLoaded(self, event):
		print "Base loaded"
		self.mainPanel.OnBaseLoaded(event)

	def OnCollectionsLoaded(self, event):
		print "Collections loaded"
		self.mainPanel.OnCollectionsLoaded(event)

	def OnElementsLoaded(self, event):
		print "Elements loaded"
		self.mainPanel.OnElementsLoaded(event)

	def OnCategoryLoaded(self, event):
		print "Categories loaded"
		self.mainPanel.OnCategoryLoaded(event)

	def OnPropertyLoaded(self, event):
		print "Properties loaded"
		self.mainPanel.OnPropertyLoaded(event)

	def OnProductLoaded(self, event):
		print "Products loaded"
		self.mainPanel.OnProductLoaded(event)

	def OnOrderLoaded(self, event):
		print "Orders loaded"
		self.mainPanel.OnOrderLoaded(event)

	def OnElementUpdate(self, event):
		self.mainPanel.OnElementUpdate(event)

	def OnElementBaseLogin(self, event):
		print "Base login", event.success

		if event.success:
			self.elementBaseThread.loadBaseFlag = True
			self.OnChangePanel(False)
		else:
			self.OnChangePanel(True)

	def OnChangePanel(self, login = False):
		if login:
			self.loginPanel.Show()
			self.mainPanel.Hide()
			self.sizer = wx.BoxSizer(wx.VERTICAL)
			self.sizer.Add(self.loginPanel, 1, wx.EXPAND)
			self.SetSizer(self.sizer)

			self.loginPanel.SetSizerAndFit(self.loginPanel.combinedSizer)
			#self.loginPanel.mainSizer.Fit(self)
		else:
			self.loginPanel.Hide()
			self.mainPanel.Show()
			self.sizer = wx.BoxSizer(wx.VERTICAL)
			self.sizer.Add(self.mainPanel, 1, wx.EXPAND | wx.ALL)
			self.SetSizer(self.sizer)

			self.mainPanel.SetSizerAndFit(self.mainPanel.combinedSizer)
			#self.mainPanel.mainSizer.Fit(self)

		self.Layout()
	

if __name__ == '__main__':
	app = wx.App(False)
	app.frame = MainFrame(None, "IC Locker",
		(1300,900),
		style = wx.SYSTEM_MENU | wx.CAPTION | wx.CLOSE_BOX | wx.MINIMIZE_BOX | wx.MAXIMIZE_BOX | wx.RESIZE_BORDER)
	app.frame.Show()
	app.SetTopWindow(app.frame)

	app.MainLoop()

