

polygon.messages = {}

polygon.messages.inbox = {
    page: 1, 
    display: function(page) {
        if(page == undefined) page = this.page;
        else this.page = page;
        $(".messages-inbox-container .items").empty();
        $(".messages-inbox-container .no-items").addClass("d-none");
		$(".messages-inbox-container .pagination").addClass("d-none");
		$(".messages-inbox-container .loading").removeClass("d-none");

		$.post('/api/messages/GetInboxMessages', {page: page}, function(data)
		{ 
			$(".messages-inbox-container .loading").addClass("d-none");

			polygon.pagination.handle("messages-inbox", page, data.pages);
			if(data.messages == undefined) return $(".messages-inbox-container .no-items").text(data.message).removeClass("d-none");
			polygon.populate(data.messages, ".messages-inbox-container .template .inbox-message", ".messages-inbox-container .items");
			for(let i = 0; i < data.messages.length; i++)
			{
				if(data.messages[i].TimeRead == 0)
				{
					$(".inbox-message .card[data-message-id='"+data.messages[i].MessageId+"']").css("background-color","#bdbebe");
				}
			}
		});
    }
}

$(function()
{ 
	polygon.messages.inbox.display(); 

	polygon.pagination.register("messages-inbox", polygon.messages.inbox.display); 
});

$("button[data-control='sendMessage']").on("click", this, function(){
	var button = this; 
	polygon.button.busy(button);
	$.post('/api/messages/SendMessage', {recipientId: $(this).attr("data-recipient-id"), subject: $("#subject").val(), body: $("#body").val()}, function(data)
	{ 

		if(data.success){ toastr["success"]("Successfully sent message."); }
		else{ toastr["error"](data.message); }
  
		polygon.button.active(button);
	});
})

$("button[id='reply-btn']").on("click", this, function(){
	$("div .reply-box").toggle();
}) 

$("button[data-control='sendReply']").on("click", this, function(){
	var button = this; 
	polygon.button.busy(button);
	$.post('/api/messages/SendMessage', {recipientId: $(this).attr("data-recipient-id"), messageId: $(this).attr("data-message-id"), body: $("#reply").val()}, function(data)
	{ 

		if(data.success){ toastr["success"]("Successfully sent message."); }
		else{ toastr["error"](data.message); }
  
		polygon.button.active(button);
	});
}) 

$(".messages-inbox-container input[id='select-all-inbox']").on("click", this, function(){
	var selectedMessages = []
    $(':checkbox').prop('checked', this.checked);
	$('.items :checkbox').each(function() {
		if(this.checked) {
			selectedMessages.push($(this).attr("data-message-check-id"));
			console.log(selectedMessages)
		}
	});
})

$(".items :checkbox").on("click", this, function(){
	var selectedMessage = []
	$('.items :checkbox').each(function() {
		if(this.checked) {
			selectedMessages.push($(this).attr("data-message-check-id"));
			console.log(selectedMessage)
		}
	});
})
//imma rewrite this shit later dw