from urllib2 import Request, urlopen, URLError
import urllib2
import gspread
import json


icLockerURL = 'http://ic-locker.com/'

class ElementBase():
	def __init__(self):

		self.token = ""
		self.lastError = None
		self.loggedIn = False

		self.icLockerLoginUrl = icLockerURL + "api/login"

		if __name__ == '__main__':
			self.Login("tilen.majerle@mesi.si","tilen123")

			self.loadBase()
		
	def loadBase(self):
		self.getCollections()

		self.elements = {}
		self.categories = {}
		self.properties = {}
		self.products = {}
		self.orders = {}
		
		for i in range(len(self.collections)):
			self.getElements(self.collections[i]['id'])
			self.getCategories(self.collections[i]['id'])
			self.getProperties(self.collections[i]['id'])
			self.getProducts(self.collections[i]['id'])
			self.getOrders(self.collections[i]['id'])
			
		return True

	def getCollections(self):
		collections = self.GET(icLockerURL+'api/collections')

		if collections:
			self.collections = {}
			for i in range(len(collections['Values'])):
				self.collections[i] = collections['Values'][i]['Collection']

	def getElements(self, collection_id):
		elementsUrl = icLockerURL + "api/collection/" + str(collection_id) + "/elements"
		elements = self.GET(elementsUrl)

		if elements:
			self.elements[collection_id] = {}
			for i in range(len(elements['Values'])):
				self.elements[collection_id][i] = elements['Values'][i]
		
	def getCategories(self, collection_id):
		categoriesUrl = icLockerURL + "api/collection/" + str(collection_id) + "/categories"
		categories = self.GET(categoriesUrl)
			
		if categories:
			self.categories[collection_id] = {}
			for i in range(len(categories['Values'])):
				self.categories[collection_id][i] = categories['Values'][i]
			
	def getProperties(self, collection_id):
		propertiesUrl = icLockerURL + "api/collection/" + str(collection_id) + "/properties"
		properties = self.GET(propertiesUrl)

		if properties:
			self.properties[collection_id] = {}
			for i in range(len(properties['Values'])):
				self.properties[collection_id][i] = properties['Values'][i]

	def getProducts(self, collection_id):
		productsUrl = icLockerURL + "api/collection/" + str(collection_id) + "/products"
		products = self.GET(productsUrl)

		if products:
			self.products[collection_id] = {}
			for i in range(len(products['Values'])):
				self.products[collection_id][i] = products['Values'][i]
	
	def getOrders(self, collection_id):
		ordersUrl = icLockerURL + "api/collection/" + str(collection_id) + "/orders"
		orders = self.GET(ordersUrl)

		if orders:
			self.orders[collection_id] = {}
			for i in range(len(orders['Values'])):
				self.orders[collection_id][i] = orders['Values'][i]
	

	def HTTPErrorHandler(self, e):
		#print e, dir(e)
		if e.code == 401:
			print "[ElementBase]Token changed"
			self.loggedIn = False
		elif e.code == 401:
			print "[ElementBase]Bed request"
		elif e.code == 500:
			print "[ElementBase]Tilen zajebal"


	def POST(self, url, payload):
		request = Request(url, data=json.dumps(payload), headers={'Authentication' : self.token, 'Content-Type' : 'application/json'})
		try:
			response = urlopen(request)
			status = json.loads(response.read())
			return status
		except urllib2.HTTPError, e:
			print '[ElementBase]HTTPError', str(e.code)
			self.HTTPErrorHandler(e)
		except urllib2.URLError, e:
			print '[ElementBase]HTTPExceURLErrorption', str(e.reason)
		except Exception, e:
			print '[ElementBase]generic exception:', e

		self.lastError = e
		return False

	def GET(self, url):
		request = Request(url, headers={'Authentication' : self.token, 'Content-Type' : 'application/json'})
		try:
			response = urlopen(request)
			status = json.loads(response.read())
			return status
		except urllib2.HTTPError, e:
			print '[ElementBase]HTTPError', str(e.code)
			self.HTTPErrorHandler(e)
		except urllib2.URLError, e:
			print '[ElementBase]HTTPExceURLErrorption', str(e.reason)
		except Exception, e:
			print '[ElementBase]generic exception:', e

		self.lastError = e
		return False


	def Login(self,username,password):
		payload = {"username":username,'password':password}

		respond = self.POST(self.icLockerLoginUrl, payload)

		self.loggedIn = False

		if respond:
			self.token = respond['Usertoken']['token']
			self.loggedIn = True
		
		return self.loggedIn

	def UpdateQuantity(self, collection_id, element_num, value):
		element_id = self.elements[collection_id][element_num]['Element']['id']
		elementsUrl = icLockerURL + "api/collection/" + str(collection_id) + "/elements"

		payload = {"quantity":int(value)}

		respond = self.POST(elementsUrl+'/edit/'+str(element_id), payload)
		if respond:
			print "[ElementBase]Write: ", respond['Element']['name'], int(respond['Element']['quantity'])
			self.elements[collection_id][element_num]['Element']['quantity'] = int(respond['Element']['quantity'])
			return True
			#if respond['validate_success'] == False:
			#	print "Failed to write"
		return False

	def ReadQuantity(self, collection_id, element_num):
		element_id = self.elements[collection_id][element_num]['Element']['id']
		elementsUrl = icLockerURL + "api/collection/" + str(collection_id) + "/elements"

		respond = self.GET(elementsUrl+'/view/'+str(element_id))

		if respond:
			print "[ElementBase]Read: ", respond['Element']['name'], int(respond['Element']['quantity'])
			self.elements[collection_id][element_num]['Element']['quantity'] = int(respond['Element']['quantity'])

			return int(respond['Element']['quantity'])

		#TODO: respond not correct?????
		return False
		


	def AddElement(self, category_id, element_name, element_quantity, element_comment, properties = {} ):
		payload = {"element_name":element_name, 'element_quantity': element_quantity, 'element_comment':element_comment, 'property':properties}
		elementsUrl = icLockerURL + "api/collection/" + str(collection_id) + "/elements"

		respond = self.POST(elementsUrl+'/add/'+str(category_id), payload)

		if respond:
			print "[ElementBase]Add: ", respond['Element']['element_name'], int(respond['Element']['element_quantity'])
			return True

		return False

	def EditElement(self, element_id, element_name, element_quantity, element_comment, category_id, properties = {} ):
		if properties:
			payload = {"element_name":element_name, 'element_quantity': element_quantity, 'element_comment':element_comment, 'category_id':int(category_id), 'property':properties}
		else:
			payload = {"element_name":element_name, 'element_quantity': element_quantity, 'element_comment':element_comment, 'category_id':int(category_id)}

		respond = self.POST(self.elementsUrl+'/edit/'+str(element_id), payload)

		if respond:
			print "[ElementBase]Edit: ", respond['Element']['element_name'], int(respond['Element']['element_quantity'])
			return True

		return False

	def DeleteElement(self, element_num):
		element_id = self.elementDictionary[element_num]['id']

		respond = self.POST(self.elementsUrl+'/delete/'+str(element_id), "")

		if respond:
			if respond['status']:
				print "[ElementBase]Delete: ", element_id
			else:
				print "[ElementBase]Failed to delete element: ", element_id

			return True

		return False

	def AddProduct(self, name, description, elements = {} ):
		payload = {"product_name":name, 'product_description': description, 'element':elements}

		respond = self.POST(self.productsUrl+'/add', payload)

		if respond:
			print "[ElementBase]Product Add: "#, status['element_name'], int(status['element_quantity'])
			return True

		return False

	def ViewProduct(self, product_id):
		respond = self.GET(self.productsUrl+'/view/'+str(product_id))

		if respond:
			print "[ElementBase]Product view: ", respond['Product']['product_name'], respond['Product']['product_description'], int(respond['Product']['elements_count'])

			product_elements = []
			for i in range(len(respond['Element'])):
				dic_position = -1
				for j in range(len(self.elementDictionary)):
					if self.elementDictionary[j]['id'] == int(respond['Element'][i]['element_id']):
						dic_position = j
						break

				product_elements.append({'id': respond['Element'][i]['element_id'], 
										'count': respond['Element'][i]['ElementProduct']['element_count'], 
										'dic_position':dic_position})
				

			return product_elements

		#TODO: respond not correct?????
		return False

	def DeleteProduct(self, product_id):
		respond = self.POST(self.productsUrl+'/delete/'+str(product_id), "")

		if respond:
			if respond['status']:
				print "[ElementBase]Product delete: ", product_id
			else:
				print "[ElementBase]Failed to delete product: ", product_id
			return True

		return False


if __name__ == '__main__':
	ElementBase();

