import wx

import pymouse
import time
import webbrowser

from FarnellSpreadSheetAdd import FarnellAddFrame
from EditElement import EditElement

class ElementOptions(wx.PopupWindow):
	def __init__(self, parent, collection_id, element_num, position):
		"""Constructor"""
		wx.PopupWindow.__init__(self, parent, wx.SIMPLE_BORDER)

		panel = wx.Panel(self)
		self.parent = parent
		self.panel = panel
		self.collection_id = collection_id
		self.element_num = element_num
		self.position = position
		self.element = self.parent.elementBaseThread.elementBase.elements[collection_id][self.element_num]
		#panel.SetBackgroundColour("LIGTH BLUE")

		self.edit_button = wx.Button(self.panel,-1,"Edit",pos=(10,10))
		self.add_farnell_button = wx.Button(self.panel,-1,"Add to farnell list",pos=(10,10))
		self.open_datasheet_button = wx.Button(self.panel,-1,"Open Datasheet",pos=(10,10))
		self.delete_button = wx.Button(self.panel,-1,"Delete",pos=(10,10))

		#if self.element['farnell'] == "":
		self.add_farnell_button.Hide()
		#if self.element['datasheet'] == "":
		self.open_datasheet_button.Hide()


		self.mainSizer = wx.BoxSizer(wx.VERTICAL)
		self.mainSizer.Add(self.edit_button,1,wx.EXPAND)
		self.mainSizer.Add(self.add_farnell_button,1,wx.EXPAND)
		self.mainSizer.Add(self.open_datasheet_button,1,wx.EXPAND)
		self.mainSizer.Add(self.delete_button,1,wx.EXPAND)

		self.panel.SetSizerAndFit(self.mainSizer)
		self.mainSizer.Fit(self)

		self.edit_button.Bind(wx.EVT_LEAVE_WINDOW, self.OnLeave)
		self.edit_button.Bind(wx.EVT_ENTER_WINDOW, self.OnEnter)
		self.add_farnell_button.Bind(wx.EVT_LEAVE_WINDOW, self.OnLeave)
		self.add_farnell_button.Bind(wx.EVT_ENTER_WINDOW, self.OnEnter)
		self.open_datasheet_button.Bind(wx.EVT_LEAVE_WINDOW, self.OnLeave)
		self.open_datasheet_button.Bind(wx.EVT_ENTER_WINDOW, self.OnEnter)
		self.delete_button.Bind(wx.EVT_LEAVE_WINDOW, self.OnLeave)
		self.delete_button.Bind(wx.EVT_ENTER_WINDOW, self.OnEnter)

		self.edit_button.Bind(wx.EVT_BUTTON, self.OnEdit)
		self.add_farnell_button.Bind(wx.EVT_BUTTON, self.OnAddFarnell)
		self.open_datasheet_button.Bind(wx.EVT_BUTTON, self.OnOpenDataSheet)
		self.delete_button.Bind(wx.EVT_BUTTON, self.OnDelete)

		self.SetPosition(self.position)

		self.leavs = 0
		
		wx.CallAfter(self.Refresh)  
		

	def MouseOnPopUp(self):
		m = pymouse.PyMouse()
		mouse_pos = m.position()
		if mouse_pos[0] > self.position[0]+5 and mouse_pos[0] < (self.position[0]+self.GetSize()[0]-5):
			if mouse_pos[1] > self.position[1]+5 and mouse_pos[1] < (self.position[1]+self.GetSize()[1]-5):
				return True
		return False


	def OnLeave(self,event):
		self.leavs -= 1
		#print self.leavs
		if self.leavs <= 0:
			#print self.MouseOnPopUp
			if self.MouseOnPopUp() == False:
				self.Show(False)
			#self.panel.Unbind(wx.EVT_LEAVE_WINDOW)
			#self.Destroy()

	def OnEnter(self,event):
		self.leavs += 1
		#print self.leavs

	def OnEdit(self,event):
		self.Show(False)
		self.ElementEdit = EditElement(self.parent, element_num = self.element_num)
		self.ElementEdit.Show(True)

	def OnDelete(self,event):
		self.Show(False)
		dlg = wx.PasswordEntryDialog (self.panel, 'If you really want to delete the element '+self.element['name']+', enter the right password:',"Delete element")

		if self.parent.elementBaseThread.elementBase.logInPassword:
			if (dlg.ShowModal() == wx.ID_OK):
				dlg.Destroy()
				if dlg.GetValue() == self.parent.elementBaseThread.elementBase.logInPassword:
					self.parent.elementBaseThread.DeleteElementList.append(self.element_num)
				else:
					msg_dlg = wx.MessageDialog(self.panel, 'Incorrect password',"Error", style = wx.ICON_ERROR | wx.OK)

					if (msg_dlg.ShowModal() == wx.ID_OK):
						msg_dlg.Destroy()
		else:
			msg_dlg = wx.MessageDialog(self.panel, 'Not logged it',"Error", style = wx.ICON_ERROR | wx.OK)

			if (msg_dlg.ShowModal() == wx.ID_OK):
				msg_dlg.Destroy()

		

	def OnAddFarnell(self,event):
		self.FarnellAdd = FarnellAddFrame(self.parent, num = self.element['farnell'], description = self.element['name'] + ": " + self.element['comment'])
		self.FarnellAdd.Show(True)
		self.Show(False)

	def OnOpenDataSheet(self,event):
		webbrowser.open(self.element['datasheet'].replace("\\","/"), new=0, autoraise=True)
		self.Show(False)