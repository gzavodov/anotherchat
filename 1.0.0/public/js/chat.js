if(typeof(UserChatScope) === "undefined")
{
	UserChatScope = { personal: "personal", global: "global" };
}
if(typeof(UserChat) === "undefined")
{
	UserChat = function()
	{
		this._settings = {};
		this._userId = 0;
		this._userWrapper = null;
		this._userPanel = null;
		this._errorWrapper = null;
		this._isMessagesShown = false;
		this._messageWrapper = null;
		this._messagePanel = null;
		this._messageInput = null;
		this._sendButton = null;
		this._socketClient = null;
		
		this._scope = UserChatScope.personal;
		
		this._enableWrite = true;
		this._enableDelete = false;
	};
	
	UserChat.prototype = 
	{
		initialize: function(settings)
		{
			this._settings = $.isPlainObject(settings) ? settings : {};
			this._serviceUrl = this.getSetting("serviceUrl", "");	

			this._scope = this.getSetting("scope", UserChatScope.personal);
			this._enableWrite = this.getSetting("enableWrite", true);
			this._enableDelete = this.getSetting("enableDelete", false);
			
			this._errorWrapper = $("#" + this.getSetting("errorWrapperId"));
			
			this._userId = parseInt(this.getSetting("userId", 0));
			this._userWrapper = $("#" + this.getSetting("userWrapperId"));
			this._userPanel = UserPanel.create({ container: document.getElementById(this.getSetting("userListId")), chat: this });
			
			this._messageWrapper = $("#" + this.getSetting("messageWrapperId"));
			this._messagePanel = MessagePanel.create({ container: document.getElementById(this.getSetting("messageListId")), data: [], chat: this });

			if(this._enableWrite)
			{
				this._messageInput = $("#" + this.getSetting("messageInputId"));
				this._messageInput.on("keypress", $.proxy(this.onMessageKeyPress, this));
			
				this._sendButton = $("#" + this.getSetting("sendButtonId"));
				this._sendButton.on("click", $.proxy(this.onSendButtonClick, this));
			}
			
			this._isMessagesShown = this._messageWrapper.is(":visible");
			this._socketClient = WebSocketClient.create({ chat: this });
			
		},
		getSetting: function(name, value)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : value; 
		},
		getScope: function()
		{
			return this._scope;
		},
		getUserId: function()
		{
			return this._userId;
		},
		isWritingEnabled: function()
		{
			return this._enableWrite;
		},
		isDeletionEnabled: function()
		{
			return this._enableDelete;
		},		
		login: function()
		{	
			if(!this._socketClient.isReady())
			{
				Message.create(
					{ 
						container: this._errorWrapper, 
						type: MessageType.error,
						text: "Could not login - WebSocket is not ready." 
					}
				).layout();
				return;
			}
			
			this._socketClient.send({ topic: "login", data: { user_id: this._userId } });
		},
		loadHistory: function(filter)
		{
			if(!this._socketClient.isReady())
			{
				Message.create(
					{ 
						container: this._errorWrapper, 
						type: MessageType.error,
						text: "Could not load history - WebSocket is not ready." 
					}
				).layout();
				return;
			}
			
			filter["scope"] = this._scope;
			this._socketClient.send(
				{ 
					topic: "request", 
					data: { user_id: this._userId, filter: filter } 
				}
			);
		},
		resolveUserName: function(userId)
		{
			return this._userPanel.resolveUserName(userId);
		},
		processUserPanelItemClick: function(panel, item)
		{
			if(!this._isMessagesShown)
			{
				this._isMessagesShown = true;
				this._messageWrapper.show();
				this._userWrapper.removeClass("col-lg-12").addClass("col-lg-3");
			}
			this._messagePanel.cleanLayout();
			
			item.resetUnreadMessageCount();
			this.loadHistory({ "target_user_id": item.getUserId() });
		},
		createMessage: function()
		{
			if(!this._enableWrite)
			{
				return;
			}	
			
			var message = this._messageInput.val();
			if(message === "")
			{
				alert("Message is empty. Please, write down Message.");
				return;
			}
			
			var addresseeId = this._userPanel.getSelectedUserId();
			if(addresseeId <= 0)
			{
				alert("Addressee is not selected. Please, select an Addressee.");
				return;
			}
			
			if(!this._socketClient.isReady())
			{
				Message.create(
					{ 
						container: this._errorWrapper, 
						type: MessageType.error,
						text: "Could not create message - WebSocket is not ready." 
					}
				).layout();
				return;
			}
			
			this._socketClient.send(
				{ 
					topic: "new_message", 
					data: { message: message, to_id: addresseeId } 
				}
			);
			this._messageInput.val("");
		},
		deleteMessage: function(id)
		{
			if(!this._enableDelete)
			{
				return;
			}
			
			if(!this._socketClient.isReady())
			{
				Message.create(
					{ 
						container: this._errorWrapper, 
						type: MessageType.error,
						text: "Could not delete message - WebSocket is not ready." 
					}
				).layout();
				return;
			}			
			
			if(!window.confirm("Do you really want to remove this message?"))
			{
				return;
			}
			
			this._socketClient.send(
				{ 
					topic: "request", 
					data: { user_id: this._userId, method: "delete", id: id } 
				}
			);
		},
		registerMessages: function(data)
		{
			if(!this._messagePanel.hasLayout())
			{
				this._messagePanel.setData(data);
				this._messagePanel.layout();
			}
			else
			{
				var selectedUserId = this._userPanel.getSelectedUserId();
				var messageCount = 0;
				for (var i = 0; i < data.length; i++)
				{
					var d = data[i];
					var addresserId = parseInt(d["from_id"]);
					var addresseeId = parseInt(d["to_id"]);
					
					if(selectedUserId == addresserId || selectedUserId == addresseeId)
					{
						this._messagePanel.createItem(d);
						messageCount++;
					}
					else
					{
						var addresserItem = this._userPanel.getItemById(addresserId);
						if(addresserItem)
						{
							addresserItem.incrementUnreadMessageCount(1);
						}
					}
				}
				
				if(messageCount > 0)
				{
					this._messagePanel.scrollToBottom();
				}				
			}				
		},
		onSendButtonClick: function(e)
		{
			this.createMessage();
			e.preventDefault();
		},
		onMessageKeyPress: function(e)
		{
			if(e.keyCode === 10 || (e.ctrlKey && e.keyCode === 13))
			{
				this.createMessage();
			}
		},
		onSocketOpen: function(e)
		{
			this.login();
		},
		onSocketMessage: function(e)
		{
			var message = JSON.parse(e.data);
			switch(message.topic) 
			{
			  case "messages":
			  {
				this.registerMessages(message.data);
				break;
			  }
			  case "users":
			  {
				var selectedUserId = this._userPanel.getSelectedUserId();
				this._userPanel.cleanLayout();
				this._userPanel.setData(message.data.users);
				this._userPanel.layout();
				if(selectedUserId > 0)
				{
					this._userPanel.setSelectedUserId(selectedUserId);
				}
				break;
			  }
			  case "deletion":
			  {
				  var messageId = parseInt(message.data.id);
				  if(messageId > 0)
				  {
					  this._messagePanel.removeItemByMessageId(messageId);
				  }
			  }
			  default:
				break;
			}
		},
		onSocketError: function(e)
		{
			Message.create(
				{ 
					container: this._errorWrapper, 
					type: MessageType.error,
					text: "An error has occurred in WebSocket client." 
				}
			).layout();
		},
		onSocketClose: function(e)
		{
			Message.create(
				{ 
					container: this._errorWrapper, 
					type: MessageType.warning,
					text: "WebSocket was closed." 
				}
			).layout();
		}
	};

	UserChat.create = function(settings)
	{
		var self = new UserChat();
		self.initialize(settings);
		return self;
	};	
}

if(typeof(UserPanel) === "undefined")
{
	UserPanel = function()
	{
		this._settings = {};
		this._chat = null;
		this._container = null;
		this._data = [];
		this._names = {};
		this._items = {};
		this._selectedItem = null;
	};
	
	UserPanel.prototype = 
	{
		initialize: function(settings)
		{
			this._settings = $.isPlainObject(settings) ? settings : {};
			this._chat = this.getSetting("chat");
			this._container = this.getSetting("container");
		},
		getSetting: function(name, value)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : value; 
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data)
		{
			this._data = data;
		},
		layout: function()
		{
			var currentUserId = this._chat.getUserId();
			for(var i = 0; i < this._data.length; i++)
			{
				var d = this._data[i];
				var id = d["id"];
				var name = d["name"];
				
				this._names[id] = name;
				
				if(name === "admin" || id === currentUserId)
				{
					continue;
				}
				
				var item = UserPanelItem.create({ id: id,  data: d, container: this._container, panel: this });
				this._items[id] = item;
				
				item.layout();
			}
		},
		cleanLayout: function()
		{
			this._selectedItem = null;
			for(var k in this._items)
			{
				if(this._items.hasOwnProperty(k))
				{
					this._items[k].cleanLayout();
				}
			}
			this._items = {};
		},		
		getSelectedItem: function()
		{
			return this._selectedItem;
		},
		setSelectedItem: function(item)
		{
			if(this._selectedItem)
			{
				this._selectedItem.setSelected(false);
				this._selectedItem = null;
			}
			
			if(item)
			{
				this._selectedItem = item;
				this._selectedItem.setSelected(true);
			}
		},		
		getSelectedUserId: function()
		{
			return this._selectedItem ? this._selectedItem.getUserId() : 0;
		},
		setSelectedUserId: function(userId)
		{			
			this.setSelectedItem(this.getItemById(userId));
		},		
		getItemById: function(itemId)
		{
			return this._items.hasOwnProperty(itemId) ? this._items[itemId] : null;
		},
		resolveUserName: function(userId)
		{			
			return this._names.hasOwnProperty(userId) ? this._names[userId] : ("[" + userId + "]");
		},
		processItemClick: function(item)
		{
			this.setSelectedItem(item);
			this._chat.processUserPanelItemClick(this, item);
		}
	};
	
	UserPanel.create = function(settings)
	{
		var self = new UserPanel();
		self.initialize(settings);
		return self;
	};
}

if(typeof(UserPanelItem) === "undefined")
{
	UserPanelItem = function()
	{
		this._settings = {};
		this._panel = null;
		this._container = null;
		this._wrapper = null;
		this._badge = null;
		this._data = [];
		this._unreadMessageCount = 0;
		this._isSelected = false;
	};
	
	UserPanelItem.prototype = 
	{
		initialize: function(settings)
		{
			this._settings = $.isPlainObject(settings) ? settings : {};
			this._panel = this.getSetting("panel");
			this._container = this.getSetting("container");
			this._data = this.getSetting("data");
		},
		getSetting: function(name, value)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : value; 
		},
		getUserId: function()
		{
			return parseInt(this._data["id"]);
		},
		getUserName: function()
		{
			return this._data["name"];
		},
		getUnreadMessageCount: function()
		{
			return this._unreadMessageCount;
		},
		setUnreadMessageCount: function(count)
		{
			count = parseInt(count);
			if(isNaN(count) || count < 0 )
			{
				count = 0;
			}
			
			this._unreadMessageCount = count;
			if(count > 0)
			{
				this._badge.text(count);
			}
			else
			{
				this._badge.empty();
			}
		},
		incrementUnreadMessageCount: function(count)
		{
			this.setUnreadMessageCount(this._unreadMessageCount + count);
		},
		decrementUnreadMessageCount: function(count)
		{
			this.setUnreadMessageCount(this._unreadMessageCount - count);
		},
		resetUnreadMessageCount: function()
		{
			this.setUnreadMessageCount(0);
		},		
		layout: function()
		{
			this._wrapper = $("<li/>", { "class": "list-group-item", click: $.proxy(this.onClick, this) });
			this._wrapper.append($("<span/>", { "class": "name", text: this._data["name"] }));
			
			this._badge = $("<span/>", { "class": "badge" });
			this._wrapper.append(this._badge);
			
			$(this._container).append(this._wrapper);
		},
		cleanLayout: function()
		{
			if(this._wrapper)
			{
				this._wrapper.remove();
				this._wrapper = null;
			}
		},		
		setSelected: function(selected)
		{
			this._isSelected = !!selected; 
			if(this._isSelected)
			{
				this._wrapper.addClass("active");
			}
			else
			{
				this._wrapper.removeClass("active");
			}
		},
		isSelected: function()
		{
			return this._isSelected;
		},
		onClick: function()
		{
			this._panel.processItemClick(this);
		}
	};
	
	UserPanelItem.create = function(settings)
	{
		var self = new UserPanelItem();
		self.initialize(settings);
		return self;
	};		
}

if(typeof(MessagePanel) === "undefined")
{
	MessagePanel = function()
	{
		this._settings = {};
		this._chat = null;
		this._container = null;
		this._message = null;
		this._data = [];
		this._items = [];
		this._hasLayout = false;
	};
	
	MessagePanel.prototype = 
	{
		initialize: function(settings)
		{
			this._settings = $.isPlainObject(settings) ? settings : {};
			this._chat = this.getSetting("chat");
			this._container = this.getSetting("container");
			this._data = this.getSetting("data");
		},
		getSetting: function(name, value)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : value; 
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data)
		{
			this._data = data;
		},
		createItem: function(data)
		{
			if(!this._hasLayout)
			{
				this._data.push(data);
			}
			else
			{
				this.hideStub();
				var item = MessagePanelItem.create({ data: data, container: this._container, chat: this._chat, panel: this });
				this._items.push(item);
				item.layout();
			}
		},
		removeItemByMessageId: function(messageId)
		{
			for(var i = 0; i < this._items.length; i++)
			{
				var item = this._items[i];
				if(item.getMessageId() == messageId)
				{
					item.cleanLayout();
					this._items.splice(i, 1);
					break;
				}
			}

			if(this._items.length === 0)
			{
				this.showStub();
			}
		},
		scrollToBottom: function()
		{
			this._container.scrollTop = this._container.scrollHeight;
		},
		hasLayout: function()
		{
			return this._hasLayout;
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}
		
			if(this._data.length === 0)
			{
				this.showStub();
			}
			else
			{
				for(var i = 0; i < this._data.length; i++)
				{
					var item = MessagePanelItem.create({ data: this._data[i], container: this._container, chat: this._chat, panel: this });
					this._items.push(item);
					item.layout();
				}
			}
			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}
			
			this.hideStub();
		
			for(var i = 0; i < this._items.length; i++)
			{
				this._items[i].cleanLayout();
			}
			this._items = [];
			
			this._hasLayout = false;
		},
		showStub: function()
		{
			if(this._message === null)
			{
				this._message = Message.create(
					{ 
						container: this._container, 
						type: MessageType.info,
						text: "There is no history for selected user.",
						enableCloseButton: false
					}
				)
				this._message.layout();
			}
		},
		hideStub: function()
		{
			if(this._message)
			{
				this._message.close();
				this._message = null;
			}
		}
	};
	
	MessagePanel.create = function(settings)
	{
		var self = new MessagePanel();
		self.initialize(settings);
		return self;
	};	
}

if(typeof(MessagePanelItem) === "undefined")
{
	MessagePanelItem = function()
	{
		this._settings = {};
		this._chat = null;
		this._panel = null;
		this._container = null;
		this._wrapper = null;
		this._deleteButton = null;
		this._data = [];
	};
	
	MessagePanelItem.prototype = 
	{
		initialize: function(settings)
		{
			this._settings = $.isPlainObject(settings) ? settings : {};
			this._chat = this.getSetting("chat");
			this._panel = this.getSetting("panel");
			this._container = this.getSetting("container");
			this._data = this.getSetting("data");
		},
		getSetting: function(name, value)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : value; 
		},
		getMessageId: function()
		{
			return this._data.hasOwnProperty("id") ? parseInt(this._data["id"]) : 0;
		},
		htmlencode: function(str)
		{
			return document.createElement('a').appendChild(document.createTextNode(str)).parentNode.innerHTML;
		},
		prepareHtml: function(str)
		{
			return this.htmlencode(str).replace(/(\r\n|\r|\n)/g, "<br/>");
		},
		layout: function()
		{
			var userId = this._chat.getUserId();
			var addresserId = parseInt(this._data["from_id"]);
			var addresseeId = parseInt(this._data["to_id"]);
			
			this._wrapper = $("<div/>", { "class": "media msg" });
			var body = $("<div/>", { "class": "media-body" });
			
			var time = $("<small/>", { "class": "pull-right time" });
			time.append($("<i/>", { "class": "fa fa-clock-o" } ));
			time.append($(document.createTextNode(" " + jQuery.timeago(this._data["created_at"]))));
			body.append(time);
			
			var html = "";
			if(userId === addresserId)
			{
				this._wrapper.addClass("bg-warning");
				html = "<i title='Outgoing message' alt='Outgoing message' class='fa fa-arrow-circle-o-up'></i> " 
					+ this.htmlencode(this._chat.resolveUserName(addresseeId));
			}
			else if(userId === addresseeId)
			{
				this._wrapper.addClass("bg-info");
				html = "<i title='Incoming message' alt='Incoming message' class='fa fa-arrow-circle-o-down'></i> " 
					+ this.htmlencode(this._chat.resolveUserName(addresserId));
			}
			else
			{
				html = this.htmlencode(this._chat.resolveUserName(addresserId)) 
					+ " <i class='fa fa-long-arrow-right'></i> " 
					+ this.htmlencode(this._chat.resolveUserName(addresseeId));
			}
			
			if(this._chat.isDeletionEnabled())
			{
				body.css("position", "relative");
				this._deleteButton = $(
					"<small/>", 
					{ "html": "<button class='btn btn-xs pull-bottom' title='Remove this message'><span class='glyphicon glyphicon-trash'></span></button>" }
				);
				this._deleteButton.css("position", "absolute").css("bottom", "0").css("right", "0");
				this._deleteButton.on("click", $.proxy(this.onDeleteButtonClick, this));
				body.append(this._deleteButton);
			}
			
			body.append($("<h5/>", { "class": "media-heading", html: html }));			
			body.append($("<small/>", { "class": "col-lg-10", html: this.prepareHtml(this._data["message"]) }));
			
			this._wrapper.append(body);
			
			$(this._container).append(this._wrapper);
		},
		cleanLayout: function()
		{
			if(this._wrapper)
			{
				this._wrapper.remove();
				this._wrapper = null;
			}
		},
		onDeleteButtonClick: function(e)
		{
			this._chat.deleteMessage(this._data["id"]);
		}
	};
	
	MessagePanelItem.create = function(settings)
	{
		var self = new MessagePanelItem();
		self.initialize(settings);
		return self;
	};
}

if(typeof(MessageType) === "undefined")
{
	MessageType = { error: 1, warning: 2, info: 3 };
}

if(typeof(Message) === "undefined")
{
	Message = function()
	{
		this._settings = {};
		this._container = null;
		this._type = MessageType.error;
		this._text = "";
		this._wrapper = null;
		this._closeButton = null;
	};
	
	Message.prototype = 
	{
		initialize: function(settings)
		{
			this._settings = $.isPlainObject(settings) ? settings : {};
			this._container = this.getSetting("container");
			this._type = this.getSetting("type", MessageType.error);
			this._text = this.getSetting("text", "");
		},
		getSetting: function(name, value)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : value; 
		},		
		layout: function()
		{
			var className = "alert fade in";
			if(this._type == MessageType.error)
			{
				className = "alert alert-danger fade in";
			}
			else if(this._type == MessageType.warning)
			{
				className = "alert alert-warning fade in";
			}
			else if(this._type == MessageType.info)
			{
				className = "alert alert-info fade in";
			}			
			this._wrapper = $("<div/>", { "class": className });
			
			if(this.getSetting("enableCloseButton", true))
			{
				this._closeButton = $("<a/>", { "class": "close", text: "x" });
				this._closeButton.on("click", $.proxy(this.onCloseButtonClick, this));
				this._wrapper.append(this._closeButton);				
			}			
			
			var title = "";
			if(this._type == MessageType.error)
			{
				title = "Error!";
			}
			else if(this._type == MessageType.warning)
			{
				title = "Warning.";
			}
			
			if(title !== "")
			{
				this._wrapper.append($("<strong/>", { text: title + " " }));
			}
			
			this._wrapper.append(document.createTextNode(this._text));
			$(this._container).append(this._wrapper);
		},
		cleanLayout: function()
		{
			if(this._wrapper)
			{
				this._wrapper.remove();
				this._wrapper = null;
			}
		},
		close: function()
		{
			this.cleanLayout();
		},
		onCloseButtonClick: function(e)
		{
			this.close();
			e.preventDefault();
		}		
	};
	
	Message.create = function(settings)
	{
		var self = new Message();
		self.initialize(settings);
		return self;
	};	
}

if(typeof(WebSocketClient) === "undefined")
{
	WebSocketClient = function()
	{
		this._settings = {};
		this._socket = null;
		this._chat = null;
	};

	WebSocketClient.prototype = 
	{
		initialize: function(settings)
		{
			this._settings = $.isPlainObject(settings) ? settings : {};
			var chat = this._chat = this.getSetting("chat");
			
			this._socket = new WebSocket(WebSocketClient.serviceUrl);
			this._socket.onopen = function(e) { chat.onSocketOpen(e); };
			this._socket.onclose = function(e) { chat.onSocketClose(e); };
			this._socket.onmessage = function(e) { chat.onSocketMessage(e); };
			this._socket.onerror = function(e) { chat.onSocketError(e); };			
		},
		getSetting: function(name, value)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : value; 
		},
		getSocket: function()
		{
			return this._socket;
		},
		send: function(data)
		{
			this._socket.send(JSON.stringify(data));
		},
		isReady: function()
		{
			return this._socket.readyState == 1;
		}		
	};
	WebSocketClient.serviceUrl = "";
	WebSocketClient.create = function(settings)
	{
		var self = new WebSocketClient();
		self.initialize(settings);
		return self;
	};
}
