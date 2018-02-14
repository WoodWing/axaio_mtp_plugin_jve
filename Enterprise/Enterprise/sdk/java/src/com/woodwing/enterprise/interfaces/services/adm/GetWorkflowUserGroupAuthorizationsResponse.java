/**
 * GetWorkflowUserGroupAuthorizationsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class GetWorkflowUserGroupAuthorizationsResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.adm.WorkflowUserGroupAuthorization[] workflowUserGroupAuthorizations;

    private com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups;

    private com.woodwing.enterprise.interfaces.services.adm.Status[] statuses;

    private com.woodwing.enterprise.interfaces.services.adm.Section[] sections;

    public GetWorkflowUserGroupAuthorizationsResponse() {
    }

    public GetWorkflowUserGroupAuthorizationsResponse(
           com.woodwing.enterprise.interfaces.services.adm.WorkflowUserGroupAuthorization[] workflowUserGroupAuthorizations,
           com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups,
           com.woodwing.enterprise.interfaces.services.adm.Status[] statuses,
           com.woodwing.enterprise.interfaces.services.adm.Section[] sections) {
           this.workflowUserGroupAuthorizations = workflowUserGroupAuthorizations;
           this.userGroups = userGroups;
           this.statuses = statuses;
           this.sections = sections;
    }


    /**
     * Gets the workflowUserGroupAuthorizations value for this GetWorkflowUserGroupAuthorizationsResponse.
     * 
     * @return workflowUserGroupAuthorizations
     */
    public com.woodwing.enterprise.interfaces.services.adm.WorkflowUserGroupAuthorization[] getWorkflowUserGroupAuthorizations() {
        return workflowUserGroupAuthorizations;
    }


    /**
     * Sets the workflowUserGroupAuthorizations value for this GetWorkflowUserGroupAuthorizationsResponse.
     * 
     * @param workflowUserGroupAuthorizations
     */
    public void setWorkflowUserGroupAuthorizations(com.woodwing.enterprise.interfaces.services.adm.WorkflowUserGroupAuthorization[] workflowUserGroupAuthorizations) {
        this.workflowUserGroupAuthorizations = workflowUserGroupAuthorizations;
    }


    /**
     * Gets the userGroups value for this GetWorkflowUserGroupAuthorizationsResponse.
     * 
     * @return userGroups
     */
    public com.woodwing.enterprise.interfaces.services.adm.UserGroup[] getUserGroups() {
        return userGroups;
    }


    /**
     * Sets the userGroups value for this GetWorkflowUserGroupAuthorizationsResponse.
     * 
     * @param userGroups
     */
    public void setUserGroups(com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups) {
        this.userGroups = userGroups;
    }


    /**
     * Gets the statuses value for this GetWorkflowUserGroupAuthorizationsResponse.
     * 
     * @return statuses
     */
    public com.woodwing.enterprise.interfaces.services.adm.Status[] getStatuses() {
        return statuses;
    }


    /**
     * Sets the statuses value for this GetWorkflowUserGroupAuthorizationsResponse.
     * 
     * @param statuses
     */
    public void setStatuses(com.woodwing.enterprise.interfaces.services.adm.Status[] statuses) {
        this.statuses = statuses;
    }


    /**
     * Gets the sections value for this GetWorkflowUserGroupAuthorizationsResponse.
     * 
     * @return sections
     */
    public com.woodwing.enterprise.interfaces.services.adm.Section[] getSections() {
        return sections;
    }


    /**
     * Sets the sections value for this GetWorkflowUserGroupAuthorizationsResponse.
     * 
     * @param sections
     */
    public void setSections(com.woodwing.enterprise.interfaces.services.adm.Section[] sections) {
        this.sections = sections;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetWorkflowUserGroupAuthorizationsResponse)) return false;
        GetWorkflowUserGroupAuthorizationsResponse other = (GetWorkflowUserGroupAuthorizationsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.workflowUserGroupAuthorizations==null && other.getWorkflowUserGroupAuthorizations()==null) || 
             (this.workflowUserGroupAuthorizations!=null &&
              java.util.Arrays.equals(this.workflowUserGroupAuthorizations, other.getWorkflowUserGroupAuthorizations()))) &&
            ((this.userGroups==null && other.getUserGroups()==null) || 
             (this.userGroups!=null &&
              java.util.Arrays.equals(this.userGroups, other.getUserGroups()))) &&
            ((this.statuses==null && other.getStatuses()==null) || 
             (this.statuses!=null &&
              java.util.Arrays.equals(this.statuses, other.getStatuses()))) &&
            ((this.sections==null && other.getSections()==null) || 
             (this.sections!=null &&
              java.util.Arrays.equals(this.sections, other.getSections())));
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
        if (getWorkflowUserGroupAuthorizations() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getWorkflowUserGroupAuthorizations());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getWorkflowUserGroupAuthorizations(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getUserGroups() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getUserGroups());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getUserGroups(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getStatuses() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getStatuses());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getStatuses(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getSections() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSections());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSections(), i);
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
        new org.apache.axis.description.TypeDesc(GetWorkflowUserGroupAuthorizationsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetWorkflowUserGroupAuthorizationsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("workflowUserGroupAuthorizations");
        elemField.setXmlName(new javax.xml.namespace.QName("", "WorkflowUserGroupAuthorizations"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "WorkflowUserGroupAuthorization"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "WorkflowUserGroupAuthorization"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userGroups");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserGroups"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "UserGroup"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "UserGroup"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("statuses");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Statuses"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Status"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Status"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sections");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Sections"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Section"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Section"));
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
