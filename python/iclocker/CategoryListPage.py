import wx

import pymouse
import time


LIST_COLORS = ['#F7F7F7', '#FFFFFF', '#FCF8E3']

#73879C

def hexToColour(value):
	value = value.lstrip('#')
	lv = len(value)
	t = tuple(int(value[i:i + lv // 3], 16) for i in range(0, lv, lv // 3))
	return wx.Colour(t[0],t[1],t[2])

class CategoryListPage(wx.Panel):
	def __init__(self, panelParent, mainFrame):
		wx.Panel.__init__(self, panelParent)
		self.mainFrame = mainFrame
		self.elementBaseThread = mainFrame.elementBaseThread

		self.popWin = None
		self.order = True

		self.category_lc = wx.ListCtrl(self, wx.ID_ANY, wx.DefaultPosition, wx.Size(700,600), wx.LC_REPORT | wx.LC_HRULES | wx.LC_VRULES)
		self.category_lc.Bind( wx.EVT_LIST_ITEM_SELECTED, self.onCategorySelected)
		self.category_lc.Bind( wx.EVT_LIST_ITEM_RIGHT_CLICK, self.onCategoryRightClick)
		self.category_lc.Bind( wx.EVT_LIST_COL_CLICK, self.onColumClick)
		self.category_lc.SetFont(self.mainFrame.font)

		self.category_lc.InsertColumn(0, 'Name')
		self.category_lc.InsertColumn(1, 'Number')
		self.category_lc.InsertColumn(2, 'Description')
		self.category_lc.InsertColumn(3, 'Element count')

		self.category_lc.SetColumnsOrder([1,0,2,3])

		self.mainSizer = wx.BoxSizer(wx.VERTICAL)
		self.mainSizer.Add(self.category_lc,1,wx.EXPAND)

		self.Bind(wx.EVT_SIZE, self.OnResize, self)

		self.SetSizerAndFit(self.mainSizer)
		self.mainSizer.Fit(self)

		self.setListWidth()

	def setListWidth(self):
		size = self.GetSize()[0]

		self.category_lc.SetColumnWidth(0, size*0.15) 
		self.category_lc.SetColumnWidth(1, size*0.30)
		self.category_lc.SetColumnWidth(2, size*0.40)
		self.category_lc.SetColumnWidth(3, size*0.15)

	def addToList(self, position, element_number):
		self.category_lc.InsertStringItem(position, str(self.categoryDic[element_number]["Category"]['name']))
		self.category_lc.SetStringItem(position, 1, str(self.categoryDic[element_number]["Category"]['id']))
		self.category_lc.SetStringItem(position, 2, str(self.categoryDic[element_number]["Category"]['description']))
		self.category_lc.SetStringItem(position, 3, str(self.categoryDic[element_number]["Category"]['elements_count']))

		self.category_lc.SetItemBackgroundColour(position, hexToColour(LIST_COLORS[position%3]))
		self.category_lc.SetItemData(position, element_number)

	def UpdateList(self):
		self.category_lc.DeleteAllItems()

		for row in range(len(self.categoryDic)):
			self.addToList(row,row)

	def OnBaseLoaded(self, event):
		self.collection_id = event.collection_id

		self.categoryDic = self.elementBaseThread.elementBase.categories[self.collection_id]

		self.UpdateList()

	def OnResize(self, event):
		event.Skip()
		self.setListWidth()

	def onCategorySelected(self, event):
		category_num = self.category_lc.GetItemData(self.category_lc.GetFirstSelected())

	def onCategoryRightClick(self, event):
		category_num = self.category_lc.GetItemData(self.category_lc.GetFirstSelected())

	def sortColume(self, colume_num, element_path, is_string):
		self.category_lc.DeleteAllItems()
		
		self.order = not self.order
	
		for category_num in range(len(self.categoryDic)):

			if category_num:
				found=False
				for position in range(self.category_lc.GetItemCount()):
					if is_string:
						str_diff = cmp(self.categoryDic[category_num]['Category'][element_path],self.category_lc.GetItemText(position,col=colume_num))
						if (self.order and str_diff >= 0 ) or (not self.order and str_diff < 0):
							self.addToList(position,category_num)
							found = True
							break
					else:
						int_diff = int(self.categoryDic[category_num]['Category'][element_path]) - int(self.category_lc.GetItemText(position,col=colume_num))
						if (self.order and int_diff >= 0) or (not self.order and int_diff < 0):
							self.addToList(position,category_num)
							found = True
							break
				if found == False:
					self.addToList(self.category_lc.GetItemCount(),category_num)
			else:
				#first element
				self.addToList(0,category_num)

	def onColumClick(self,event):
		column_clicked = event.m_col

		if column_clicked == 0: #name
			self.sortColume(column_clicked,'name',1)

		elif column_clicked == 1: #number
			self.category_lc.DeleteAllItems()

			for category_num in range(len(self.categoryDic)):
				if self.order:
					self.addToList(0,category_num)
				else:
					self.addToList(self.category_lc.GetItemCount(),category_num)

			self.order = not self.order

		elif column_clicked == 3: #Element count
			self.sortColume(column_clicked,'elements_count',0)
		
