/**
 * GetWorkflowUserGroupAuthorizationsRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class GetWorkflowUserGroupAuthorizationsRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes;

    private java.math.BigInteger publicationId;

    private java.math.BigInteger issueId;

    private java.math.BigInteger userGroupId;

    private java.math.BigInteger[] workflowUserGroupAuthorizationIds;

    public GetWorkflowUserGroupAuthorizationsRequest() {
    }

    public GetWorkflowUserGroupAuthorizationsRequest(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes,
           java.math.BigInteger publicationId,
           java.math.BigInteger issueId,
           java.math.BigInteger userGroupId,
           java.math.BigInteger[] workflowUserGroupAuthorizationIds) {
           this.ticket = ticket;
           this.requestModes = requestModes;
           this.publicationId = publicationId;
           this.issueId = issueId;
           this.userGroupId = userGroupId;
           this.workflowUserGroupAuthorizationIds = workflowUserGroupAuthorizationIds;
    }


    /**
     * Gets the ticket value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the requestModes value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @return requestModes
     */
    public com.woodwing.enterprise.interfaces.services.adm.Mode[] getRequestModes() {
        return requestModes;
    }


    /**
     * Sets the requestModes value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @param requestModes
     */
    public void setRequestModes(com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes) {
        this.requestModes = requestModes;
    }


    /**
     * Gets the publicationId value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @return publicationId
     */
    public java.math.BigInteger getPublicationId() {
        return publicationId;
    }


    /**
     * Sets the publicationId value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @param publicationId
     */
    public void setPublicationId(java.math.BigInteger publicationId) {
        this.publicationId = publicationId;
    }


    /**
     * Gets the issueId value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @return issueId
     */
    public java.math.BigInteger getIssueId() {
        return issueId;
    }


    /**
     * Sets the issueId value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @param issueId
     */
    public void setIssueId(java.math.BigInteger issueId) {
        this.issueId = issueId;
    }


    /**
     * Gets the userGroupId value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @return userGroupId
     */
    public java.math.BigInteger getUserGroupId() {
        return userGroupId;
    }


    /**
     * Sets the userGroupId value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @param userGroupId
     */
    public void setUserGroupId(java.math.BigInteger userGroupId) {
        this.userGroupId = userGroupId;
    }


    /**
     * Gets the workflowUserGroupAuthorizationIds value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @return workflowUserGroupAuthorizationIds
     */
    public java.math.BigInteger[] getWorkflowUserGroupAuthorizationIds() {
        return workflowUserGroupAuthorizationIds;
    }


    /**
     * Sets the workflowUserGroupAuthorizationIds value for this GetWorkflowUserGroupAuthorizationsRequest.
     * 
     * @param workflowUserGroupAuthorizationIds
     */
    public void setWorkflowUserGroupAuthorizationIds(java.math.BigInteger[] workflowUserGroupAuthorizationIds) {
        this.workflowUserGroupAuthorizationIds = workflowUserGroupAuthorizationIds;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetWorkflowUserGroupAuthorizationsRequest)) return false;
        GetWorkflowUserGroupAuthorizationsRequest other = (GetWorkflowUserGroupAuthorizationsRequest) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.requestModes==null && other.getRequestModes()==null) || 
             (this.requestModes!=null &&
              java.util.Arrays.equals(this.requestModes, other.getRequestModes()))) &&
            ((this.publicationId==null && other.getPublicationId()==null) || 
             (this.publicationId!=null &&
              this.publicationId.equals(other.getPublicationId()))) &&
            ((this.issueId==null && other.getIssueId()==null) || 
             (this.issueId!=null &&
              this.issueId.equals(other.getIssueId()))) &&
            ((this.userGroupId==null && other.getUserGroupId()==null) || 
             (this.userGroupId!=null &&
              this.userGroupId.equals(other.getUserGroupId()))) &&
            ((this.workflowUserGroupAuthorizationIds==null && other.getWorkflowUserGroupAuthorizationIds()==null) || 
             (this.workflowUserGroupAuthorizationIds!=null &&
              java.util.Arrays.equals(this.workflowUserGroupAuthorizationIds, other.getWorkflowUserGroupAuthorizationIds())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getRequestModes() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRequestModes());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRequestModes(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPublicationId() != null) {
            _hashCode += getPublicationId().hashCode();
        }
        if (getIssueId() != null) {
            _hashCode += getIssueId().hashCode();
        }
        if (getUserGroupId() != null) {
            _hashCode += getUserGroupId().hashCode();
        }
        if (getWorkflowUserGroupAuthorizationIds() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getWorkflowUserGroupAuthorizationIds());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getWorkflowUserGroupAuthorizationIds(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetWorkflowUserGroupAuthorizationsRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetWorkflowUserGroupAuthorizationsRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestModes");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestModes"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Mode"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Mode"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publicationId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublicationId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issueId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "IssueId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userGroupId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserGroupId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("workflowUserGroupAuthorizationIds");
        elemField.setXmlName(new javax.xml.namespace.QName("", "WorkflowUserGroupAuthorizationIds"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Id"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Id"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
