from langgraph.graph import StateGraph, END
from src.agents.retriever_agent import RetrieverAgent
from src.agents.reasoning_agent import ReasoningAgent
from src.agents.utility_agent import UtilityAgent
from src.agents.clarification_agent import ClarificationAgent
from .state import AgentState

def create_workflow(hybrid_retriever):
    retriever_agent = RetrieverAgent(hybrid_retriever)
    reasoning_agent = ReasoningAgent()
    utility_agent = UtilityAgent()
    clarification_agent = ClarificationAgent()

    workflow = StateGraph(AgentState)

    workflow.add_node("retrieve", retriever_agent.retrieve)
    workflow.add_node("reason", reasoning_agent.reason)
    workflow.add_node("summarize", utility_agent.summarize)
    workflow.add_node("clarify", clarification_agent.clarify)

    workflow.set_entry_point("retrieve")

    def should_reason(state: AgentState):
        return "reason" if state["documents"] else "clarify"

    workflow.add_conditional_edges(
        "retrieve",
        should_reason,
        {
            "reason": "reason",
            "clarify": "clarify",
        },
    )
    workflow.add_edge("reason", "summarize")
    workflow.add_edge("summarize", END)
    workflow.add_edge("clarify", END)


    return workflow.compile()