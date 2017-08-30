import wx
import wx.lib.newevent
import wx.lib.intctrl

import time


ADD_STRINGS = ["Name","Category","Quantity","Description"]

class EditElement(wx.Frame):
	def __init__(self, parent, element_num):
		self.l = wx.Locale(wx.LANGUAGE_ENGLISH)
		wx.Frame.__init__(self, parent, title="Element Add", style = wx.SYSTEM_MENU | wx.CAPTION | wx.CLOSE_BOX | wx.RESIZE_BORDER)

		self.parent = parent 
		self.element_num = element_num
		self.element = self.parent.elementBaseThread.elementBase.elementDictionary[self.element_num]
			
		self.categories_str = []

		for i in range(len(self.parent.elementBaseThread.elementBase.categories)):
			self.categories_str.append(self.parent.elementBaseThread.elementBase.categories[i]['name'])

		self.create_main_panel()


		self.name_tc.SetLabel(self.element['name'])
		self.description_tc.SetLabel(self.element['comment'])
		self.quantity_tc.SetValue(int(self.element['quantity']))
		for i in range(len(self.parent.elementBaseThread.elementBase.categories)):
			if self.parent.elementBaseThread.elementBase.categories[i]['id'] == self.element['category_id']:
				self.category_tc.SetSelection(i)
				self.OnCategoryChange(None)
				break

		category = self.parent.elementBaseThread.elementBase.categories[self.category_tc.GetSelection()]
		for i in range(len(category['properties'])):
			for j in range(len(self.element['propertys'])):
				if category['properties'][i]['property_id'] == self.element['propertys'][j]['property_id']:
					if category['properties'][i]['property_data_type'] == 1 or category['properties'][i]['property_data_type'] == 2: #int and float
						self.properties_ic[i].SetValue(int(self.element['propertys'][j]['ElementProperty']['property_value']))
					else:
						self.properties_tc[i].SetLabel(self.element['propertys'][j]['ElementProperty']['property_value'])
					break

		self.Show(True)


	def create_main_panel(self):
		self.panel = wx.Panel(self,wx.ID_ANY)
		self.SetBackgroundColour(wx.WHITE)
		self.font = wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_NORMAL)
		self.fontB = wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD)
		self.fontS = wx.Font(10, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_NORMAL)


		self.name_st = wx.StaticText(self.panel, label="Name: ", size=(100,25))
		self.name_st.SetFont(self.font)
		self.name_tc = wx.TextCtrl(self.panel, size=(200,25), style=wx.TE_RICH2)
		self.name_tc.SetFont(self.font)
		self.name_box = wx.BoxSizer(wx.HORIZONTAL)
		self.name_box.Add(self.name_st)
		self.name_box.Add(self.name_tc,1,wx.EXPAND)


		self.description_st = wx.StaticText(self.panel, label="Description: ", size=(100,25))
		self.description_st.SetFont(self.font)
		self.description_tc = wx.TextCtrl(self.panel, size=(200,25), style=wx.TE_RICH2)
		self.description_tc.SetFont(self.font)
		self.description_box = wx.BoxSizer(wx.HORIZONTAL)
		self.description_box.Add(self.description_st)
		self.description_box.Add(self.description_tc,1,wx.EXPAND)


		self.quantity_st = wx.StaticText(self.panel, label="Quantity: ", size=(100,25))
		self.quantity_st.SetFont(self.font)
		self.quantity_tc = wx.SpinCtrl(self.panel, size=(200,25), max = 100000, style=wx.TE_RICH2)
		self.quantity_tc.SetFont(self.font)
		self.quantity_box = wx.BoxSizer(wx.HORIZONTAL)
		self.quantity_box.Add(self.quantity_st)
		self.quantity_box.Add(self.quantity_tc,1,wx.EXPAND)


		self.category_st = wx.StaticText(self.panel, label="Category: ", size=(100,25))
		self.category_st.SetFont(self.font)
		self.category_tc = wx.Choice(self.panel, size=(200,25), choices=self.categories_str, style=wx.TE_RICH2)
		self.category_tc.SetFont(self.font)
		self.category_box = wx.BoxSizer(wx.HORIZONTAL)
		self.category_box.Add(self.category_st)
		self.category_box.Add(self.category_tc,1,wx.EXPAND)

		self.category_tc.Bind(wx.EVT_CHOICE, self.OnCategoryChange)


		self.properties_st = []
		self.properties_tc = []
		self.properties_ic = []
		self.properties_box = []
		self.properties_vbox = wx.BoxSizer(wx.VERTICAL)

		for i in range(10):
			self.properties_st.append(wx.StaticText(self.panel, label="", size=(100,25)))
			self.properties_st[-1].SetFont(self.font)
			self.properties_tc.append(wx.TextCtrl(self.panel, size=(200,25), style=wx.TE_RICH2))
			self.properties_tc[-1].SetFont(self.font)
			self.properties_ic.append(wx.lib.intctrl.IntCtrl(self.panel, size=(200,25), style=wx.TE_RICH2))
			self.properties_ic[-1].SetFont(self.font)

			self.properties_box.append(wx.BoxSizer(wx.HORIZONTAL))
			self.properties_box[-1].Add(self.properties_st[-1])
			self.properties_box[-1].Add(self.properties_tc[-1],1,wx.EXPAND)
			self.properties_box[-1].Add(self.properties_ic[-1],1,wx.EXPAND)
			self.properties_vbox.Add(self.properties_box[-1],0,wx.EXPAND)
			#self.properties_st[-1].Hide()
			#self.properties_tc[-1].Hide()


		self.edit_button = wx.Button(self.panel, label="Save changes to base",size=(200,40))
		self.edit_button.Bind(wx.EVT_BUTTON, self.OnEdit)
		self.edit_button.SetFont(self.font)

		#define all top level sizers
		self.mainSizer = wx.BoxSizer(wx.HORIZONTAL)
		self.hbox = wx.BoxSizer(wx.VERTICAL)

		self.hbox.AddSpacer(20)
		self.hbox.Add(self.name_box,0,wx.EXPAND)
		self.hbox.AddSpacer(2)
		self.hbox.Add(self.description_box,0,wx.EXPAND)
		self.hbox.AddSpacer(2)
		self.hbox.Add(self.quantity_box,0,wx.EXPAND)
		self.hbox.AddSpacer(2)
		self.hbox.Add(self.category_box,0,wx.EXPAND)
		self.hbox.AddSpacer(20)


		self.hbox.Add(self.properties_vbox,0,wx.EXPAND)
		self.hbox.Show(self.properties_vbox, show = False, recursive = True)


		self.hbox.AddSpacer(20)
		self.hbox.Add(self.edit_button,0,wx.CENTER)
		self.hbox.AddSpacer(20)

		#whole structure
		self.mainSizer.AddSpacer(30)
		self.mainSizer.Add(self.hbox,1,wx.EXPAND)
		self.mainSizer.AddSpacer(30)

		self.panel.SetSizerAndFit(self.mainSizer)
		self.mainSizer.Fit(self)

	def OnCategoryChange(self,event):
		self.hbox.Show(self.properties_vbox, show = True, recursive = True)

		category = self.parent.elementBaseThread.elementBase.categories[self.category_tc.GetSelection()]

		for i in range(len(category['properties'])):
			self.properties_st[i].SetLabel(category['properties'][i]['property_name'] + ": ")
			self.properties_vbox.Show(self.properties_box[i], show = True, recursive = True)

			if category['properties'][i]['property_data_type'] == 1 or category['properties'][i]['property_data_type'] == 2: #int and float
				self.properties_tc[i].Hide()
			else:
				self.properties_ic[i].Hide()

		for j in range(i+1,len(self.properties_box)):
			self.properties_vbox.Show(self.properties_box[j], show = False, recursive = True)


		self.SetSizeWH(500, 1000)

		self.SetSizerAndFit(self.mainSizer)
		self.mainSizer.Fit(self)

		#print self.category_tc.GetSelection()

	def OnEdit(self, event):
		if self.category_tc.GetSelection() == -1:
			msg_dlg = wx.MessageDialog(self.panel, 'No category was chosen',"Error", style = wx.ICON_ERROR | wx.OK)

			if (msg_dlg.ShowModal() == wx.ID_OK):
				msg_dlg.Destroy()
			return

		name = self.name_tc.GetLineText(0)
		category = self.parent.elementBaseThread.elementBase.categories[self.category_tc.GetSelection()]
		quantity = self.quantity_tc.GetValue()
		description = self.description_tc.GetLineText(0)

		properties = {}

		#property_data_type   1: integer, 2: float, 3: string

		for i in range(len(category['properties'])):

			if category['properties'][i]['property_data_type'] == 1 or category['properties'][i]['property_data_type'] == 2: #int and float
				integer = int(self.properties_ic[i].GetLineText(0))
				if integer:
					properties[category['properties'][i]['property_id']] = integer
					pass
			else:
				text = self.properties_tc[i].GetLineText(0)
				if text:
					properties[category['properties'][i]['property_id']] = text


		#print properties
		self.parent.elementBaseThread.EditElementList.append([self.element['id'],name,quantity,description,category['id'],properties])
		self.Destroy()

		

if __name__ == '__main__':
	app = wx.App(False)
	app.frame = EditElement(None)
	app.frame.Show()
	app.SetTopWindow(app.frame)

	app.MainLoop()